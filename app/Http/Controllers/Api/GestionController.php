<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Gestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class GestionController extends Controller
{
    /**
     * Crear gestión
     * POST /gestiones
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'estado' => 'nullable|string|in:pendiente,en_proceso,completada,rechazada',
            'nombre' => 'required|string|min:1',
            'fecha' => 'nullable',
            'userId' => 'nullable',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            $errorMessage = 'Error de validación: ';
            if ($errors->has('nombre')) {
                $errorMessage .= 'El nombre de la gestión es requerido. ';
            }
            if ($errors->has('estado')) {
                $errorMessage .= 'El estado debe ser uno de: pendiente, en_proceso, completada, rechazada. ';
            }
            return response()->json([
                'error' => trim($errorMessage),
                'details' => $errors->all()
            ], 400);
        }

        try {
            // Obtener user_id: priorizar userId del request, luego usuario autenticado
            $userId = null;
            if ($request->has('userId') && $request->userId) {
                // Si userId es numérico, usarlo directamente
                if (is_numeric($request->userId)) {
                    $userId = (int)$request->userId;
                } else {
                    // Si no es numérico, podría ser documentNumber, buscar usuario
                    $user = \App\Models\User::where('document_number', $request->userId)->first();
                    if ($user) {
                        $userId = $user->id;
                    }
                }
            }
            
            // Si no se obtuvo userId, usar el usuario autenticado
            if (!$userId) {
                $userId = auth()->id();
            }

            if (!$userId) {
                return response()->json([
                    'error' => 'No se pudo determinar el usuario. Debes estar autenticado.'
                ], 401);
            }

            // Convertir fecha de timestamp (milisegundos) a DateTime si viene como número
            $fecha = now();
            if ($request->has('fecha')) {
                $fechaValue = $request->fecha;
                
                // Si la fecha es 0 o null, usar la fecha actual
                if ($fechaValue === null || $fechaValue === 0 || $fechaValue === '0') {
                    $fecha = now();
                } elseif (is_numeric($fechaValue) && $fechaValue > 0) {
                    // Si viene en milisegundos, convertir a segundos
                    $timestamp = $fechaValue > 1000000000000 
                        ? $fechaValue / 1000 
                        : $fechaValue;
                    
                    // Crear DateTime desde timestamp Unix
                    $fecha = \DateTime::createFromFormat('U', (int)$timestamp);
                    
                    // Si falla la conversión, usar fecha actual
                    if ($fecha === false) {
                        $fecha = now();
                    }
                } elseif (is_string($fechaValue) && !empty($fechaValue)) {
                    // Intentar parsear como string de fecha
                    try {
                        $fecha = new \DateTime($fechaValue);
                    } catch (\Exception $e) {
                        $fecha = now();
                    }
                }
            }

            // Establecer estado automáticamente como "pendiente" si no se proporciona
            $estado = $request->estado ?? 'pendiente';

            // Validar que el nombre no esté vacío
            $nombre = trim($request->nombre);
            if (empty($nombre)) {
                return response()->json([
                    'error' => 'El nombre de la gestión no puede estar vacío'
                ], 400);
            }

            $gestionData = [
                'estado' => $estado,
                'nombre' => $nombre,
                'fecha' => $fecha,
                'user_id' => $userId,
            ];

            $gestion = Gestion::create($gestionData);
            
            // Agregar document_url si existe
            $gestion->document_url = $gestion->document_url;

            return response()->json([
                'message' => 'Gestión creada correctamente',
                'gestion' => $gestion
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Listar gestiones
     * GET /gestiones?userId=...&estado=...&limit=20
     */
    public function list(Request $request)
    {
        try {
            $userId = $request->query('userId');
            $estado = $request->query('estado');
            $limit = $request->query('limit', 50);

            // Priorizar userId del query string
            // Si no viene, usar el ID del usuario autenticado
            $finalUserId = $userId;
            if (!$finalUserId && auth()->check()) {
                $user = auth()->user();
                // Si el usuario tiene documentNumber, buscar por documentNumber
                if ($user->document_number) {
                    $userByDoc = \App\Models\User::where('document_number', $user->document_number)->first();
                    $finalUserId = $userByDoc ? $userByDoc->id : $user->id;
                } else {
                    $finalUserId = $user->id;
                }
            }

            if (!$finalUserId) {
                return response()->json([
                    'error' => 'userId es requerido para listar gestiones'
                ], 400);
            }

            $query = Gestion::where('user_id', $finalUserId);

            if ($estado) {
                $query->where('estado', $estado);
            }

            $gestiones = $query->orderBy('fecha', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($gestion) {
                    // Agregar document_url a cada gestión
                    $gestion->document_url = $gestion->document_url;
                    return $gestion;
                });

            return response()->json([
                'message' => 'Gestiones obtenidas correctamente',
                'count' => $gestiones->count(),
                'gestiones' => $gestiones
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Subir documento a una gestión
     * POST /gestiones/{id}/document
     */
    public function uploadDocument(Request $request, $id)
    {
        // Log para debugging
        \Log::info('Upload document request', [
            'gestion_id' => $id,
            'has_file' => $request->hasFile('document'),
            'all_keys' => array_keys($request->all()),
            'content_type' => $request->header('Content-Type'),
        ]);

        $validator = Validator::make($request->all(), [
            'document' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240', // Max 10MB
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            $errorMessage = 'Error de validación: ';
            
            if ($errors->has('document')) {
                $documentErrors = $errors->get('document');
                $errorMessage .= implode(', ', $documentErrors);
            } else {
                $errorMessage .= $errors->first();
            }
            
            \Log::warning('Document upload validation failed', [
                'gestion_id' => $id,
                'errors' => $errors->all(),
            ]);
            
            return response()->json([
                'error' => $errorMessage,
                'details' => $errors->all()
            ], 400);
        }

        try {
            $gestion = Gestion::find($id);

            if (!$gestion) {
                return response()->json([
                    'error' => 'Gestión no encontrada'
                ], 404);
            }

            // Verificar que el usuario autenticado sea el dueño de la gestión
            if (auth()->id() !== $gestion->user_id) {
                return response()->json([
                    'error' => 'No tienes permiso para modificar esta gestión'
                ], 403);
            }

            $file = $request->file('document');
            $originalName = $file->getClientOriginalName();
            $extension = strtolower($file->getClientOriginalExtension());
            $mimeType = $file->getMimeType();
            
            // Generar nombre único para el archivo
            $fileName = Str::uuid() . '.' . $extension;
            $path = 'documents/gestiones/' . $fileName;

            // Eliminar documento anterior si existe
            if ($gestion->document_path) {
                Storage::disk('local')->delete($gestion->document_path);
            }

            // Verificar si es una imagen para comprimir
            $isImage = in_array($extension, ['jpg', 'jpeg', 'png']) || 
                      in_array($mimeType, ['image/jpeg', 'image/jpg', 'image/png']);

            if ($isImage) {
                // Comprimir imagen
                $storedPath = $this->compressAndStoreImage($file, $fileName, $extension);
            } else {
                // Guardar PDF u otro archivo sin comprimir
                $storedPath = Storage::disk('local')->putFileAs('documents/gestiones', $file, $fileName);
            }

            // Actualizar la gestión con la ruta del documento
            $gestion->document_path = $storedPath;
            $gestion->save();

            // Recargar la gestión para obtener los datos actualizados
            $gestion->refresh();
            
            return response()->json([
                'message' => 'Documento subido exitosamente',
                'document_path' => $storedPath,
                'document_url' => $gestion->document_url,
                'original_name' => $originalName,
                'gestion' => $gestion
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Error al subir documento', [
                'gestion_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Error al subir el documento',
                'message' => $e->getMessage(),
                'details' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Descargar/ver documento de una gestión
     * GET /gestiones/{id}/document
     */
    public function downloadDocument($id)
    {
        try {
            $gestion = Gestion::find($id);

            if (!$gestion) {
                return response()->json([
                    'error' => 'Gestión no encontrada'
                ], 404);
            }

            if (!$gestion->document_path) {
                return response()->json([
                    'error' => 'Esta gestión no tiene documento asociado'
                ], 404);
            }

            // Verificar que el usuario autenticado sea el dueño de la gestión
            if (auth()->id() !== $gestion->user_id) {
                return response()->json([
                    'error' => 'No tienes permiso para ver este documento'
                ], 403);
            }

            // Verificar que el archivo existe
            if (!Storage::disk('local')->exists($gestion->document_path)) {
                return response()->json([
                    'error' => 'El archivo no existe en el servidor'
                ], 404);
            }

            $file = Storage::disk('local')->get($gestion->document_path);
            $mimeType = Storage::disk('local')->mimeType($gestion->document_path);
            $fileName = basename($gestion->document_path);

            return response($file, 200)
                ->header('Content-Type', $mimeType)
                ->header('Content-Disposition', 'inline; filename="' . $fileName . '"');
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al descargar el documento',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar una gestión
     * DELETE /gestiones/{id}
     */
    public function delete($id)
    {
        try {
            $gestion = Gestion::find($id);

            if (!$gestion) {
                return response()->json([
                    'error' => 'Gestión no encontrada'
                ], 404);
            }

            // Verificar que el usuario autenticado sea el dueño de la gestión
            if (auth()->id() !== $gestion->user_id) {
                return response()->json([
                    'error' => 'No tienes permiso para eliminar esta gestión'
                ], 403);
            }

            // Eliminar el documento asociado si existe
            if ($gestion->document_path) {
                Storage::disk('local')->delete($gestion->document_path);
            }

            // Eliminar la gestión
            $gestion->delete();

            return response()->json([
                'message' => 'Gestión eliminada exitosamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al eliminar la gestión',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar documento de una gestión
     * DELETE /gestiones/{id}/document
     */
    public function deleteDocument($id)
    {
        try {
            $gestion = Gestion::find($id);

            if (!$gestion) {
                return response()->json([
                    'error' => 'Gestión no encontrada'
                ], 404);
            }

            if (!$gestion->document_path) {
                return response()->json([
                    'error' => 'Esta gestión no tiene documento asociado'
                ], 404);
            }

            // Verificar que el usuario autenticado sea el dueño de la gestión
            if (auth()->id() !== $gestion->user_id) {
                return response()->json([
                    'error' => 'No tienes permiso para eliminar este documento'
                ], 403);
            }

            // Eliminar el archivo del storage
            if (Storage::disk('local')->exists($gestion->document_path)) {
                Storage::disk('local')->delete($gestion->document_path);
            }

            // Limpiar la ruta en la base de datos
            $gestion->document_path = null;
            $gestion->save();

            return response()->json([
                'message' => 'Documento eliminado exitosamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al eliminar el documento',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar documentos del usuario autenticado
     * GET /storage/documents
     */
    public function listDocuments(Request $request)
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'error' => 'Usuario no autenticado'
                ], 401);
            }

            $limit = $request->query('limit', 50);
            $gestionId = $request->query('gestionId');

            // Obtener gestiones del usuario que tengan documentos
            $query = Gestion::where('user_id', $user->id)
                ->whereNotNull('document_path')
                ->where('document_path', '!=', '');

            // Filtrar por gestión específica si se proporciona
            if ($gestionId) {
                $query->where('id', $gestionId);
            }

            $gestiones = $query->orderBy('fecha', 'desc')
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            // Transformar gestiones a formato de documentos
            $documents = $gestiones->map(function ($gestion) {
                $fileName = basename($gestion->document_path);
                $originalName = $gestion->document_path;
                
                // Intentar extraer el nombre original si está disponible
                // Por ahora usamos el nombre del archivo almacenado
                
                return [
                    'id' => $gestion->id,
                    'gestionId' => $gestion->id,
                    'fileName' => $fileName,
                    'originalName' => $fileName,
                    'gestionName' => $gestion->nombre,
                    'uploadedAt' => $gestion->created_at ? $gestion->created_at->toISOString() : null,
                    'documentUrl' => $gestion->document_url,
                    'estado' => $gestion->estado,
                ];
            });

            return response()->json([
                'documents' => $documents,
                'count' => $documents->count(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al listar documentos: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'error' => 'Error al obtener los documentos',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener URL de descarga de un documento
     * GET /storage/url/{fileName}
     */
    public function getDocumentUrl($fileName)
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'error' => 'Usuario no autenticado'
                ], 401);
            }

            // Decodificar el nombre del archivo si viene codificado
            $decodedFileName = urldecode($fileName);

            // Buscar la gestión que tiene este documento
            // Buscar por el nombre del archivo en la ruta del documento
            $gestion = Gestion::where('user_id', $user->id)
                ->where(function($query) use ($decodedFileName) {
                    $query->where('document_path', 'like', '%' . $decodedFileName)
                          ->orWhere('document_path', 'like', '%' . $fileName);
                })
                ->first();

            if (!$gestion) {
                return response()->json([
                    'error' => 'Documento no encontrado o no tienes permiso para accederlo'
                ], 404);
            }

            // Usar el método downloadDocument existente para obtener la URL correcta
            // O simplemente devolver la URL de la API
            return response()->json([
                'url' => url("/api/gestiones/{$gestion->id}/document")
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al obtener URL del documento: ' . $e->getMessage(), [
                'fileName' => $fileName,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'error' => 'Error al obtener la URL del documento',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Comprimir y almacenar una imagen
     * 
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $fileName
     * @param string $extension
     * @return string Ruta del archivo almacenado
     * @throws \Exception Si no se puede procesar la imagen
     */
    private function compressAndStoreImage($file, $fileName, $extension): string
    {
        try {
            // Crear el manager de imágenes (usando GD driver)
            $manager = new ImageManager(new Driver());
            
            // Leer la imagen desde el archivo temporal
            $image = $manager->read($file->getRealPath());
            
            // Obtener dimensiones originales
            $originalWidth = $image->width();
            $originalHeight = $image->height();
            
            // Validar dimensiones máximas antes de procesar (evitar imágenes extremadamente grandes)
            $maxDimensionAllowed = 10000; // 10000px máximo
            if ($originalWidth > $maxDimensionAllowed || $originalHeight > $maxDimensionAllowed) {
                \Log::warning('Imagen con dimensiones muy grandes detectada', [
                    'width' => $originalWidth,
                    'height' => $originalHeight,
                    'max_allowed' => $maxDimensionAllowed
                ]);
                // Intentar guardar el original sin comprimir
                return Storage::disk('local')->putFileAs('documents/gestiones', $file, $fileName);
            }
            
            // Definir tamaño máximo para compresión (1920px en el lado más largo)
            $maxDimension = 1920;
            
            // Redimensionar si es necesario (manteniendo aspect ratio)
            if ($originalWidth > $maxDimension || $originalHeight > $maxDimension) {
                $image->scaleDown($maxDimension, $maxDimension);
            }
            
            // Configurar calidad de compresión según el formato
            $quality = 85; // Calidad para JPEG (0-100)
            
            // Convertir a string según el formato
            $imageData = match($extension) {
                'jpg', 'jpeg' => $image->toJpeg($quality)->toString(),
                'png' => $image->toPng(9)->toString(), // Nivel de compresión PNG (0-9, 9 = máxima compresión)
                default => $image->toJpeg($quality)->toString(),
            };
            
            // Guardar la imagen comprimida
            $path = 'documents/gestiones/' . $fileName;
            Storage::disk('local')->put($path, $imageData);
            
            return $path;
        } catch (\Exception $e) {
            // Log detallado del error
            \Log::error('Error al comprimir imagen', [
                'error' => $e->getMessage(),
                'file_name' => $fileName,
                'extension' => $extension,
                'file_size' => $file->getSize(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Intentar guardar el archivo original sin comprimir como fallback
            try {
                return Storage::disk('local')->putFileAs('documents/gestiones', $file, $fileName);
            } catch (\Exception $fallbackError) {
                // Si también falla el guardado del original, lanzar error descriptivo
                throw new \Exception(
                    'No se pudo procesar la imagen. ' .
                    'Posibles causas: dimensiones muy grandes, formato no soportado, o archivo corrupto. ' .
                    'Error original: ' . $e->getMessage()
                );
            }
        }
    }
}
