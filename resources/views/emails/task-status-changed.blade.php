@component('mail::message')
# Görev Durumu Değişti

Merhaba {{ $recipient->name }},

"**{{ $task->title }}**" görevinin durumu değişti.

**Proje:** {{ $task->project->name }}
**Değiştiren:** {{ $changedBy->name }}
**Eski Durum:** {{ $oldStatus }}
**Yeni Durum:** {{ $newStatus }}

@if($task->description)
**Açıklama:**
{{ $task->description }}
@endif

@if($task->assigned_to && $task->assignedUser)
**Atanan Kişi:** {{ $task->assignedUser->name }}
@endif

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