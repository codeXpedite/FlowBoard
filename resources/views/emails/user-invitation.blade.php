@component('mail::message')
# {{ $appName }} Platformuna Davetlisiniz!

Merhaba,

**{{ $invitedBy->name }}** sizi {{ $appName }} proje yönetim platformuna davet etti.

**Rol:** {{ $roleDisplayName }}

Bu davet **{{ $invitation->expires_at->format('d.m.Y H:i') }}** tarihine kadar geçerlidir.

@component('mail::button', ['url' => $acceptUrl])
Daveti Kabul Et
@endcomponent

Eğer bu daveti kabul etmek istemiyorsanız, bu emaili görmezden gelebilirsiniz.

**Hakkımızda:**
{{ $appName }} modern proje yönetim araçları sunan bir platformdur. Kanban board ile görev takibi, ekip iş birliği ve proje yönetimi konularında size yardımcı olur.

Bu davet linki sadece sizin email adresiniz için geçerlidir ve başkalarıyla paylaşılmamalıdır.

Teşekkürler,<br>
{{ $appName }} Ekibi
@endcomponent