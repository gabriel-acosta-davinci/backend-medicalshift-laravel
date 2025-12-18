<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Obtener todos los usuarios
     * GET /users
     */
    public function getAllUsers()
    {
        try {
            $users = User::with('address')->get()->makeHidden(['password', 'remember_token']);
            return response()->json($users);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener un usuario por ID
     * GET /users/{id}
     */
    public function getUserById($id)
    {
        try {
            $user = User::with('address')->find($id);

            if (!$user) {
                return response()->json([
                    'error' => 'Usuario no encontrado'
                ], 404);
            }

            return response()->json(
                $user->makeHidden(['password', 'remember_token'])
            );
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear usuario
     * POST /users
     */
    public function createUser(Request $request)
    {
        try {
            $userData = $request->all();
            
            // Si viene password, hashearlo
            if (isset($userData['password'])) {
                $userData['password'] = bcrypt($userData['password']);
            }

            // Separar address de userData
            $addressData = null;
            if ($request->has('address') && is_array($request->address)) {
                $addressData = $request->address;
            }

            $user = User::create($userData);

            // Crear dirección si se proporcionó
            if ($addressData) {
                $user->address()->create([
                    'street' => $addressData['street'] ?? null,
                    'number' => $addressData['number'] ?? null,
                    'floor' => $addressData['floor'] ?? null,
                    'apartment' => $addressData['apartment'] ?? null,
                    'city' => $addressData['city'] ?? null,
                    'province' => $addressData['province'] ?? null,
                ]);
            }

            $user->load('address');

            return response()->json(
                $user->makeHidden(['password', 'remember_token']),
                201
            );
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar usuario
     * PUT /users/{id}
     */
    public function updateUser(Request $request, $id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'error' => 'Usuario no encontrado'
                ], 404);
            }

            $updateData = $this->buildUpdateData($request->all(), $user);
            $user->update($updateData);

            // Manejar actualización de dirección
            $this->updateAddress($user, $request->all());

            // Cargar la relación de dirección para la respuesta
            $user->load('address');

            return response()->json([
                'message' => 'Usuario actualizado correctamente',
                'user' => $user->fresh()->makeHidden(['password', 'remember_token'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar usuario autenticado (usando token)
     * PUT /users/me
     */
    public function updateCurrentUser(Request $request)
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'error' => 'Usuario no autenticado'
                ], 401);
            }

            $updateData = $this->buildUpdateData($request->all(), $user);
            $user->update($updateData);

            // Manejar actualización de dirección
            $this->updateAddress($user, $request->all());

            // Cargar la relación de dirección para la respuesta
            $user->load('address');

            return response()->json([
                'message' => 'Usuario actualizado correctamente',
                'user' => $user->fresh()->makeHidden(['password', 'remember_token'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al actualizar usuario',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar usuario
     * DELETE /users/{id}
     */
    public function deleteUser($id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'error' => 'Usuario no encontrado'
                ], 404);
            }

            $user->delete();

            return response()->json([
                'message' => 'Usuario eliminado correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Función auxiliar para construir los datos de actualización
     */
    private function buildUpdateData(array $requestData, User $currentUser): array
    {
        $updateData = [];

        // Mapeo de campos camelCase (frontend) a snake_case (base de datos)
        $allowedFields = [
            'phone_number' => 'phoneNumber',
            'email' => 'email',
            'date_of_birth' => 'dateOfBirth',
            'marital_status' => 'maritalStatus',
            'cbu' => 'cbu',
            'associate_number' => 'associateNumber',
            'plan' => 'plan',
        ];

        foreach ($allowedFields as $dbField => $requestField) {
            if (isset($requestData[$requestField])) {
                $updateData[$dbField] = $requestData[$requestField];
            }
        }

        // La dirección se maneja por separado en updateAddress()
        return $updateData;
    }

    /**
     * Función auxiliar para actualizar la dirección del usuario
     */
    private function updateAddress(User $user, array $requestData): void
    {
        $addressData = null;

        // Si viene address como objeto
        if (isset($requestData['address']) && is_array($requestData['address'])) {
            $addressData = $requestData['address'];
        } else {
            // Si vienen campos individuales de dirección
            if (isset($requestData['street']) || isset($requestData['number']) || 
                isset($requestData['floor']) || isset($requestData['apartment']) || 
                isset($requestData['city']) || isset($requestData['province'])) {
                $addressData = [
                    'street' => $requestData['street'] ?? null,
                    'number' => $requestData['number'] ?? null,
                    'floor' => $requestData['floor'] ?? null,
                    'apartment' => $requestData['apartment'] ?? null,
                    'city' => $requestData['city'] ?? null,
                    'province' => $requestData['province'] ?? null,
                ];
            }
        }

        if ($addressData) {
            // Si el usuario ya tiene una dirección, actualizarla; si no, crearla
            if ($user->address) {
                $user->address()->update([
                    'street' => $addressData['street'] ?? $user->address->street,
                    'number' => $addressData['number'] ?? $user->address->number,
                    'floor' => $addressData['floor'] ?? $user->address->floor,
                    'apartment' => $addressData['apartment'] ?? $user->address->apartment,
                    'city' => $addressData['city'] ?? $user->address->city,
                    'province' => $addressData['province'] ?? $user->address->province,
                ]);
            } else {
                $user->address()->create([
                    'street' => $addressData['street'] ?? null,
                    'number' => $addressData['number'] ?? null,
                    'floor' => $addressData['floor'] ?? null,
                    'apartment' => $addressData['apartment'] ?? null,
                    'city' => $addressData['city'] ?? null,
                    'province' => $addressData['province'] ?? null,
                ]);
            }
        }
    }
}
