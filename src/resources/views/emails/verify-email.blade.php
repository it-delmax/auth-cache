@component('mail::message')
# Verifikujte email

Kliknite na dugme da potvrdite adresu i aktivirate nalog na **{{ $app }}**.

@component('mail::button', ['url' => $url])
Potvrdi email
@endcomponent

Ako niste tražili registraciju, ignorišite poruku.

Hvala,<br>
{{ $app }}
@endcomponent