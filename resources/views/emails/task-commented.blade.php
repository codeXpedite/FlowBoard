@component('mail::message')
# Görevde Yeni Yorum

Merhaba {{ $recipient->name }},

"**{{ $task->title }}**" görevinde yeni bir yorum yapıldı.

**Proje:** {{ $task->project->name }}
**Yorumu Yapan:** {{ $commenter->name }}

@component('mail::panel')
{{ $comment }}
@endcomponent

@if($task->assigned_to && $task->assignedUser)
**Atanan Kişi:** {{ $task->assignedUser->name }}
@endif

**Görev Durumu:** {{ $task->taskStatus->name }}

@if($task->due_date)
**Bitiş Tarihi:** {{ $task->due_date->format('d.m.Y H:i') }}
@endif

@component('mail::button', ['url' => $projectUrl])
Görevi Görüntüle
@endcomponent

Bu görev hakkında daha fazla bilgi almak için proje sayfasını ziyaret edebilirsiniz.

Teşekkürler,<br>
{{ config('app.name') }}
@endcomponent