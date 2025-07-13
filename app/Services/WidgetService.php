<?php

namespace App\Services;

use App\Models\Widget;
use App\Models\WidgetType;
use App\Models\WidgetConfiguration;
use App\Models\Dashboard;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Class WidgetService
 * 
 * Handles widget management operations including creation, configuration,
 * and data processing for the Analytics Hub dashboard system.
 * 
 * @package App\Services
 */
class WidgetService
{
    /**
     * Create a new widget
     * 
     * @param array $data
     * @return Widget
     */
    public function createWidget(array $data): Widget
    {
        return Widget::create($data);
    }

    /**
     * Update an existing widget
     * 
     * @param Widget $widget
     * @param array $data
     * @return Widget
     */
    public function updateWidget(Widget $widget, array $data): Widget
    {
        $widget->update($data);
        return $widget->fresh();
    }

    /**
     * Delete a widget
     * 
     * @param Widget $widget
     * @return bool
     */
    public function deleteWidget(Widget $widget): bool
    {
        return $widget->delete();
    }

    /**
     * Get widgets for a dashboard
     * 
     * @param Dashboard $dashboard
     * @return Collection
     */
    public function getDashboardWidgets(Dashboard $dashboard): Collection
    {
        return $dashboard->widgets()->with('widgetType')->get();
    }

    /**
     * Get available widget types
     * 
     * @return Collection
     */
    public function getAvailableWidgetTypes(): Collection
    {
        return WidgetType::where('is_active', true)->get();
    }

    /**
     * Update widget configuration
     * 
     * @param string $widgetId
     * @param array $config
     * @return void
     */
    public function updateWidgetConfiguration(string $widgetId, array $config): void
    {
        foreach ($config as $key => $value) {
            WidgetConfiguration::updateOrCreate(
                [
                    'widget_id' => $widgetId,
                    'key' => $key
                ],
                [
                    'value' => $value
                ]
            );
        }
    }

    /**
     * Get widget configuration
     * 
     * @param string $widgetId
     * @return array
     */
    public function getWidgetConfiguration(string $widgetId): array
    {
        $configurations = WidgetConfiguration::where('widget_id', $widgetId)->get();
        
        $config = [];
        foreach ($configurations as $configuration) {
            $config[$configuration->key] = $configuration->value;
        }
        
        return $config;
    }

    /**
     * Search widgets by title or description
     * 
     * @param string $query
     * @return Collection
     */
    public function searchWidgets(string $query): Collection
    {
        return Widget::where('title', 'like', '%' . $query . '%')
            ->orWhere('description', 'like', '%' . $query . '%')
            ->get();
    }

    /**
     * Get widgets by type
     * 
     * @param string $typeName
     * @return Collection
     */
    public function getWidgetsByType(string $typeName): Collection
    {
        return Widget::whereHas('widgetType', function ($query) use ($typeName) {
            $query->where('name', $typeName);
        })->get();
    }

    /**
     * Import widget from data
     * 
     * @param string $dashboardId
     * @param array $importData
     * @param string $userId
     * @return Widget
     */
    public function importWidget(string $dashboardId, array $importData, string $userId): Widget
    {
        $widgetData = $importData['widget'];
        $widgetData['dashboard_id'] = $dashboardId;
        $widgetData['created_by'] = $userId;
        
        // Find widget type by name
        if (isset($importData['widget_type']['name'])) {
            $widgetType = WidgetType::where('name', $importData['widget_type']['name'])->first();
            if ($widgetType) {
                $widgetData['widget_type_id'] = $widgetType->id;
            }
        }
        
        $widget = Widget::create($widgetData);
        
        // Import configurations if provided
        if (isset($importData['configuration'])) {
            foreach ($importData['configuration'] as $key => $value) {
                WidgetConfiguration::create([
                    'widget_id' => $widget->id,
                    'key' => $key,
                    'value' => $value
                ]);
            }
        }
        
        return $widget;
    }

    /**
     * Check if user can access widget
     * 
     * @param string $userId
     * @param string $widgetId
     * @return bool
     */
    public function userCanAccessWidget(string $userId, string $widgetId): bool
    {
        $widget = Widget::find($widgetId);
        
        if (!$widget) {
            return false;
        }
        
        // Check if user created the widget or has dashboard access
        return $widget->created_by === $userId || 
               $widget->dashboard->created_by === $userId;
    }

    /**
     * Clear widget cache
     * 
     * @param string $widgetId
     * @return void
     */
    public function clearWidgetCache(string $widgetId): void
    {
        // Clear widget-specific cache
        cache()->forget("widget_data_{$widgetId}");
        cache()->forget("widget_config_{$widgetId}");
    }

    /**
     * Export widget data
     * 
     * @param string $widgetId
     * @return array
     */
    public function exportWidget(string $widgetId): array
    {
        $widget = Widget::with(['widgetType', 'configurations'])->find($widgetId);
        
        if (!$widget) {
            throw new \Exception('Widget not found');
        }
        
        $exportData = [
            'widget' => [
                'name' => $widget->name,
                'title' => $widget->title,
                'description' => $widget->description,
                'width' => $widget->width,
                'height' => $widget->height,
                'refresh_interval' => $widget->refresh_interval,
                'settings' => $widget->settings,
                'data_source' => $widget->data_source
            ],
            'widget_type' => [
                'name' => $widget->widgetType->name
            ],
            'configuration' => []
        ];
        
        // Add configurations
        foreach ($widget->configurations as $config) {
            $exportData['configuration'][$config->key] = $config->value;
        }
        
        return $exportData;
    }

    /**
     * Record widget load time for performance tracking
     * 
     * @param string $widgetId
     * @param int $loadTimeMs
     * @return void
     */
    public function recordWidgetLoadTime(string $widgetId, int $loadTimeMs): void
    {
        $key = "widget_load_times_{$widgetId}";
        $loadTimes = cache()->get($key, []);
        $loadTimes[] = [
            'time' => $loadTimeMs,
            'timestamp' => now()
        ];
        
        // Keep only last 100 records
        if (count($loadTimes) > 100) {
            $loadTimes = array_slice($loadTimes, -100);
        }
        
        cache()->put($key, $loadTimes, 3600); // Cache for 1 hour
    }

    /**
     * Get widget performance metrics
     * 
     * @param string $widgetId
     * @return array
     */
    public function getWidgetPerformanceMetrics(string $widgetId): array
    {
        $key = "widget_load_times_{$widgetId}";
        $loadTimes = cache()->get($key, []);
        
        if (empty($loadTimes)) {
            return [
                'average_load_time' => 0,
                'min_load_time' => 0,
                'max_load_time' => 0,
                'total_loads' => 0
            ];
        }
        
        $times = array_column($loadTimes, 'time');
        
        return [
            'average_load_time' => round(array_sum($times) / count($times), 2),
            'min_load_time' => min($times),
            'max_load_time' => max($times),
            'total_loads' => count($times)
        ];
    }

    /**
     * Update widget position
     * 
     * @param string $widgetId
     * @param array $position
     * @return bool
     */
    public function updateWidgetPosition(string $widgetId, array $position): bool
    {
        try {
            $widget = Widget::findOrFail($widgetId);
            
            $widget->update([
                'position_x' => $position['x'] ?? $position['position_x'] ?? $widget->position_x,
                'position_y' => $position['y'] ?? $position['position_y'] ?? $widget->position_y,
                'width' => $position['width'] ?? $widget->width,
                'height' => $position['height'] ?? $widget->height
            ]);
            
            // Clear widget cache
            $this->clearWidgetCache($widgetId);
            
            Log::info('Widget position updated', [
                'widget_id' => $widgetId,
                'position' => $position
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to update widget position', [
                'widget_id' => $widgetId,
                'position' => $position,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Clone a widget
     * 
     * @param string $widgetId
     * @return Widget
     */
    public function cloneWidget(string $widgetId): Widget
    {
        $originalWidget = Widget::with('configurations')->find($widgetId);
        
        if (!$originalWidget) {
            throw new \Exception('Widget not found');
        }
        
        // Create new widget with copied data
        $newWidgetData = $originalWidget->toArray();
        unset($newWidgetData['id'], $newWidgetData['created_at'], $newWidgetData['updated_at'], $newWidgetData['deleted_at']);
        $newWidgetData['title'] = $originalWidget->title . ' (Copy)';
        $newWidgetData['name'] = $originalWidget->name . '_copy_' . time();
        
        $newWidget = Widget::create($newWidgetData);
        
        // Clone configurations
        foreach ($originalWidget->configurations as $config) {
            WidgetConfiguration::create([
                'widget_id' => $newWidget->id,
                'key' => $config->key,
                'value' => $config->value,
                'type' => $config->type,
                'is_encrypted' => $config->is_encrypted,
                'description' => $config->description,
                'validation_rules' => $config->validation_rules,
                'default_value' => $config->default_value
            ]);
        }
        
        return $newWidget;
    }

    /**
     * Validate data source
     * 
     * @param string $dataSource
     * @return bool
     */
    public function validateDataSource(string $dataSource): bool
    {
        $validDataSources = [
            'sales_data',
            'user_data', 
            'revenue_data',
            'category_data',
            'analytics_data'
        ];
        
        return in_array($dataSource, $validDataSources);
    }

    /**
     * Get dashboard layout
     * 
     * @param string $dashboardId
     * @return array
     */
    public function getDashboardLayout(string $dashboardId): array
    {
        $widgets = Widget::where('dashboard_id', $dashboardId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
        
        return [
            'widgets' => $widgets->toArray(),
            'grid_size' => [
                'columns' => 12,
                'rows' => 'auto'
            ],
            'total_widgets' => $widgets->count()
        ];
    }

    /**
     * Validate widget configuration
     * 
     * @param array $configuration
     * @param WidgetType $widgetType
     * @return bool
     */
    public function validateConfiguration(array $configuration, WidgetType $widgetType): bool
    {
        // Check required fields based on widget type
        if ($widgetType->name === 'chart') {
            return isset($configuration['chart_type']) && isset($configuration['data_source']);
        }
        
        if ($widgetType->name === 'table') {
            return isset($configuration['data_source']);
        }
        
        // Basic validation for other types
        return !empty($configuration);
    }

    /**
     * Hide a widget
     * 
     * @param string $widgetId
     * @return bool
     */
    public function hideWidget(string $widgetId): bool
    {
        try {
            $widget = Widget::findOrFail($widgetId);
            $widget->is_active = false;
            $widget->save();
            
            Log::info('Widget hidden', ['widget_id' => $widgetId]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to hide widget', [
                'widget_id' => $widgetId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Show a widget
     * 
     * @param string $widgetId
     * @return bool
     */
    public function showWidget(string $widgetId): bool
    {
        try {
            $widget = Widget::findOrFail($widgetId);
            $widget->is_active = true;
            $widget->save();
            
            Log::info('Widget shown', ['widget_id' => $widgetId]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to show widget', [
                'widget_id' => $widgetId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}