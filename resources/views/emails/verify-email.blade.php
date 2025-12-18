@component('mail::message')

# ¡Hola {{ $notifiable->name }}!

Gracias por registrarte en **Medicalshift**. Para completar tu registro, por favor verifica tu dirección de correo electrónico haciendo clic en el botón de abajo.

@component('mail::button', ['url' => $verificationUrl])
Verificar mi correo electrónico
@endcomponent

Si no creaste una cuenta en Medicalshift, puedes ignorar este mensaje.

Este enlace expirará en 60 minutos.

Saludos,
El equipo de Medicalshift

@slot('subcopy')
Si tienes problemas al hacer clic en el botón "Verificar mi correo electrónico", copia y pega la siguiente URL en tu navegador:
[{{ $verificationUrl }}]({{ $verificationUrl }})
@endslot
@endcomponent

