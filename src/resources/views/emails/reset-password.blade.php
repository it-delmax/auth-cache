@component('mail::message')
# Zdravo, {{ $user->name ?? 'korisniče' }}

Zatražili ste promenu lozinke za nalog na **{{ $app }}**.

@component('mail::button', ['url' => $url])
Promeni lozinku
@endcomponent

Link važi {{ $minutes }} minuta. Ako niste vi zatražili reset, ignorišite poruku.

Hvala,<br>
{{ $app }}
@endcomponent