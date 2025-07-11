<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class ProjectTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'category',
        'color',
        'default_task_statuses',
        'default_tasks',
        'default_settings',
        'is_public',
        'created_by',
        'usage_count',
        'tags',
    ];

    protected $casts = [
        'default_task_statuses' => 'array',
        'default_tasks' => 'array',
        'default_settings' => 'array',
        'is_public' => 'boolean',
        'tags' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopePopular(Builder $query): Builder
    {
        return $query->orderBy('usage_count', 'desc');
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    public function getDefaultTaskStatusesAsObjects(): array
    {
        return collect($this->default_task_statuses)->map(function ($status, $index) {
            return [
                'name' => $status['name'] ?? "Status {$index}",
                'color' => $status['color'] ?? '#3B82F6',
                'order' => $status['order'] ?? $index,
                'description' => $status['description'] ?? null,
            ];
        })->toArray();
    }

    public function getDefaultTasksAsObjects(): array
    {
        return collect($this->default_tasks ?? [])->map(function ($task, $index) {
            return [
                'title' => $task['title'] ?? "Task {$index}",
                'description' => $task['description'] ?? null,
                'priority' => $task['priority'] ?? 'medium',
                'status_name' => $task['status_name'] ?? ($this->default_task_statuses[0]['name'] ?? 'To Do'),
                'order' => $task['order'] ?? $index,
                'due_date_offset' => $task['due_date_offset'] ?? null, // Days from project start
            ];
        })->toArray();
    }

    public function getTagsAttribute($value): array
    {
        if (is_string($value)) {
            return json_decode($value, true) ?? [];
        }
        return $value ?? [];
    }

    public static function getCategories(): array
    {
        return [
            'general' => 'Genel',
            'software' => 'Yazılım Geliştirme',
            'marketing' => 'Pazarlama',
            'design' => 'Tasarım',
            'event' => 'Etkinlik Yönetimi',
            'research' => 'Araştırma',
            'content' => 'İçerik Üretimi',
            'sales' => 'Satış',
            'hr' => 'İnsan Kaynakları',
            'finance' => 'Finans',
        ];
    }

    public function getCategoryDisplayName(): string
    {
        $categories = self::getCategories();
        return $categories[$this->category] ?? ucfirst($this->category);
    }

    public static function getDefaultTemplates(): array
    {
        return [
            [
                'name' => 'Yazılım Projesi',
                'description' => 'Yazılım geliştirme projeleri için kapsamlı şablon',
                'category' => 'software',
                'color' => '#3B82F6',
                'default_task_statuses' => [
                    ['name' => 'Backlog', 'color' => '#6B7280', 'order' => 0],
                    ['name' => 'To Do', 'color' => '#EF4444', 'order' => 1],
                    ['name' => 'In Progress', 'color' => '#F59E0B', 'order' => 2],
                    ['name' => 'Code Review', 'color' => '#8B5CF6', 'order' => 3],
                    ['name' => 'Testing', 'color' => '#06B6D4', 'order' => 4],
                    ['name' => 'Done', 'color' => '#10B981', 'order' => 5],
                ],
                'default_tasks' => [
                    ['title' => 'Proje gereksinimlerini belirle', 'priority' => 'high', 'status_name' => 'To Do'],
                    ['title' => 'Teknik dokümantasyon hazırla', 'priority' => 'high', 'status_name' => 'To Do'],
                    ['title' => 'Veritabanı tasarımını yap', 'priority' => 'high', 'status_name' => 'To Do'],
                    ['title' => 'API endpointlerini tasarla', 'priority' => 'medium', 'status_name' => 'To Do'],
                    ['title' => 'Test senaryolarını yaz', 'priority' => 'medium', 'status_name' => 'To Do'],
                    ['title' => 'CI/CD pipeline kurulumu', 'priority' => 'low', 'status_name' => 'Backlog'],
                ],
                'tags' => ['yazılım', 'geliştirme', 'teknoloji'],
                'is_public' => true,
            ],
            [
                'name' => 'Pazarlama Kampanyası',
                'description' => 'Pazarlama kampanyaları için organize şablon',
                'category' => 'marketing',
                'color' => '#EC4899',
                'default_task_statuses' => [
                    ['name' => 'Planlama', 'color' => '#6B7280', 'order' => 0],
                    ['name' => 'İçerik Üretimi', 'color' => '#F59E0B', 'order' => 1],
                    ['name' => 'İnceleme', 'color' => '#8B5CF6', 'order' => 2],
                    ['name' => 'Yayında', 'color' => '#06B6D4', 'order' => 3],
                    ['name' => 'Tamamlandı', 'color' => '#10B981', 'order' => 4],
                ],
                'default_tasks' => [
                    ['title' => 'Hedef kitle analizi', 'priority' => 'high', 'status_name' => 'Planlama'],
                    ['title' => 'Kampanya stratejisi belirle', 'priority' => 'high', 'status_name' => 'Planlama'],
                    ['title' => 'Bütçe planlaması yap', 'priority' => 'high', 'status_name' => 'Planlama'],
                    ['title' => 'Görsel tasarımları hazırla', 'priority' => 'medium', 'status_name' => 'İçerik Üretimi'],
                    ['title' => 'Metin içeriklerini yaz', 'priority' => 'medium', 'status_name' => 'İçerik Üretimi'],
                    ['title' => 'Sosyal medya planı oluştur', 'priority' => 'medium', 'status_name' => 'İçerik Üretimi'],
                ],
                'tags' => ['pazarlama', 'kampanya', 'sosyal medya'],
                'is_public' => true,
            ],
            [
                'name' => 'Etkinlik Organizasyonu',
                'description' => 'Etkinlik planlama ve organizasyon şablonu',
                'category' => 'event',
                'color' => '#10B981',
                'default_task_statuses' => [
                    ['name' => 'Planlama', 'color' => '#6B7280', 'order' => 0],
                    ['name' => 'Hazırlık', 'color' => '#F59E0B', 'order' => 1],
                    ['name' => 'Koordinasyon', 'color' => '#8B5CF6', 'order' => 2],
                    ['name' => 'Etkinlik Günü', 'color' => '#06B6D4', 'order' => 3],
                    ['title' => 'Takip', 'color' => '#10B981', 'order' => 4],
                ],
                'default_tasks' => [
                    ['title' => 'Etkinlik konseptini belirle', 'priority' => 'high', 'status_name' => 'Planlama'],
                    ['title' => 'Venue araştırması ve rezervasyon', 'priority' => 'high', 'status_name' => 'Planlama'],
                    ['title' => 'Davetiye tasarımı ve gönderimi', 'priority' => 'medium', 'status_name' => 'Hazırlık'],
                    ['title' => 'Catering ayarlamaları', 'priority' => 'medium', 'status_name' => 'Hazırlık'],
                    ['title' => 'Teknik ekipman kontrolü', 'priority' => 'high', 'status_name' => 'Koordinasyon'],
                    ['title' => 'Etkinlik sonrası değerlendirme', 'priority' => 'low', 'status_name' => 'Takip'],
                ],
                'tags' => ['etkinlik', 'organizasyon', 'planlama'],
                'is_public' => true,
            ],
            [
                'name' => 'Basit Proje',
                'description' => 'Genel amaçlı basit proje şablonu',
                'category' => 'general',
                'color' => '#6B7280',
                'default_task_statuses' => [
                    ['name' => 'Yapılacak', 'color' => '#EF4444', 'order' => 0],
                    ['name' => 'Devam Ediyor', 'color' => '#F59E0B', 'order' => 1],
                    ['name' => 'Tamamlandı', 'color' => '#10B981', 'order' => 2],
                ],
                'default_tasks' => [
                    ['title' => 'Proje gereksinimlerini listele', 'priority' => 'high', 'status_name' => 'Yapılacak'],
                    ['title' => 'İlk planlamayı yap', 'priority' => 'medium', 'status_name' => 'Yapılacak'],
                    ['title' => 'Kaynakları belirle', 'priority' => 'medium', 'status_name' => 'Yapılacak'],
                ],
                'tags' => ['genel', 'basit', 'başlangıç'],
                'is_public' => true,
            ],
        ];
    }
}