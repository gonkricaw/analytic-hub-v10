<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Widget;
use App\Models\WidgetType;
use App\Models\WidgetConfiguration;
use App\Models\Dashboard;
use App\Models\User;
use App\Models\Role;
use App\Models\UserRole;
use App\Services\WidgetService;
use App\Services\WidgetRenderingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Class WidgetsTest
 * 
 * Unit tests for widgets system functionality including widget CRUD,
 * configuration management, rendering, and dashboard integration.
 * 
 * @package Tests\Unit
 */
class WidgetsTest extends TestCase
{
    use RefreshDatabase;

    protected User $testUser;
    protected Role $testRole;
    protected Dashboard $testDashboard;
    protected WidgetType $chartWidgetType;
    protected WidgetType $tableWidgetType;
    protected Widget $testWidget;
    protected WidgetService $widgetService;
    protected WidgetRenderingService $renderingService;

    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->setUpRoleAndUser();
        $this->setUpDashboard();
        $this->setUpWidgetTypes();
        $this->setUpWidgets();
        $this->setUpServices();
    }

    /**
     * Set up test role and user
     */
    private function setUpRoleAndUser(): void
    {
        $this->testRole = Role::create([
            'name' => 'dashboard_user',
            'display_name' => 'Dashboard User',
            'description' => 'Can use dashboards'
        ]);
        
        $this->testUser = User::factory()->create([
            'email' => 'user@example.com',
            'status' => 'active',
            'terms_accepted' => true
        ]);
        
        UserRole::create([
            'user_id' => $this->testUser->id,
            'role_id' => $this->testRole->id,
            'is_active' => true,
            'assigned_at' => now()
        ]);
    }

    /**
     * Set up test dashboard
     */
    private function setUpDashboard(): void
    {
        $this->testDashboard = Dashboard::create([
            'name' => 'Test Dashboard',
            'slug' => 'test-dashboard',
            'description' => 'Dashboard for testing',
            'layout' => 'grid',
            'is_public' => false,
            'created_by' => $this->testUser->id
        ]);
    }

    /**
     * Set up widget types
     */
    private function setUpWidgetTypes(): void
    {
        $this->chartWidgetType = WidgetType::create([
            'name' => 'chart',
            'display_name' => 'Chart Widget',
            'description' => 'Display data as charts',
            'component' => 'ChartWidget',
            'config_schema' => json_encode([
                'chart_type' => ['type' => 'string', 'required' => true],
                'data_source' => ['type' => 'string', 'required' => true],
                'title' => ['type' => 'string', 'required' => false]
            ]),
            'is_active' => true
        ]);
        
        $this->tableWidgetType = WidgetType::create([
            'name' => 'table',
            'display_name' => 'Table Widget',
            'description' => 'Display data in table format',
            'component' => 'TableWidget',
            'config_schema' => json_encode([
                'data_source' => ['type' => 'string', 'required' => true],
                'columns' => ['type' => 'array', 'required' => true],
                'pagination' => ['type' => 'boolean', 'required' => false]
            ]),
            'is_active' => true
        ]);
    }

    /**
     * Set up test widgets
     */
    private function setUpWidgets(): void
    {
        $this->testWidget = Widget::create([
            'name' => 'sales_chart_widget',
            'dashboard_id' => $this->testDashboard->id,
            'widget_type_id' => $this->chartWidgetType->id,
            'title' => 'Sales Chart',
            'description' => 'Monthly sales data',
            'position_x' => 0,
            'position_y' => 0,
            'width' => 6,
            'height' => 4,
            'order_index' => 1,
            'is_active' => true,
            'created_by' => $this->testUser->id
        ]);
        
        // Create widget configuration
        WidgetConfiguration::create([
            'widget_id' => $this->testWidget->id,
            'key' => 'chart_type',
            'value' => 'bar'
        ]);
        
        WidgetConfiguration::create([
            'widget_id' => $this->testWidget->id,
            'key' => 'data_source',
            'value' => 'sales_data'
        ]);
    }

    /**
     * Set up services
     */
    private function setUpServices(): void
    {
        $this->widgetService = new WidgetService();
        $this->renderingService = new WidgetRenderingService();
    }

    /**
     * Test widget type creation
     */
    public function test_widget_type_creation(): void
    {
        $widgetTypeData = [
            'name' => 'metric',
            'display_name' => 'Metric Widget',
            'description' => 'Display single metric value',
            'component' => 'MetricWidget',
            'config_schema' => json_encode([
                'metric_name' => ['type' => 'string', 'required' => true],
                'format' => ['type' => 'string', 'required' => false]
            ]),
            'is_active' => true
        ];
        
        $widgetType = WidgetType::create($widgetTypeData);
        
        $this->assertInstanceOf(WidgetType::class, $widgetType);
        $this->assertEquals('metric', $widgetType->name);
        $this->assertEquals('Metric Widget', $widgetType->display_name);
        $this->assertEquals('MetricWidget', $widgetType->component);
        $this->assertTrue($widgetType->is_active);
        $this->assertTrue(Str::isUuid($widgetType->id));
    }

    /**
     * Test widget creation
     */
    public function test_widget_creation(): void
    {
        $widgetData = [
            'name' => 'user_table_widget',
            'dashboard_id' => $this->testDashboard->id,
            'widget_type_id' => $this->tableWidgetType->id,
            'title' => 'User Table',
            'description' => 'List of users',
            'position_x' => 6,
            'position_y' => 0,
            'width' => 6,
            'height' => 4,
            'order_index' => 2,
            'is_active' => true,
            'created_by' => $this->testUser->id
        ];
        
        $widget = Widget::create($widgetData);
        
        $this->assertInstanceOf(Widget::class, $widget);
        $this->assertEquals('User Table', $widget->title);
        $this->assertEquals($this->testDashboard->id, $widget->dashboard_id);
        $this->assertEquals($this->tableWidgetType->id, $widget->widget_type_id);
        $this->assertEquals(6, $widget->position_x);
        $this->assertEquals(6, $widget->width);
        $this->assertTrue($widget->is_active);
        $this->assertTrue(Str::isUuid($widget->id));
    }

    /**
     * Test widget update
     */
    public function test_widget_update(): void
    {
        $originalTitle = $this->testWidget->title;
        $newTitle = 'Updated Sales Chart';
        
        $this->testWidget->update(['title' => $newTitle]);
        
        $this->assertEquals($newTitle, $this->testWidget->fresh()->title);
        $this->assertNotEquals($originalTitle, $this->testWidget->fresh()->title);
    }

    /**
     * Test widget soft delete
     */
    public function test_widget_soft_delete(): void
    {
        $widgetId = $this->testWidget->id;
        
        $this->testWidget->delete();
        
        // Widget should be soft deleted
        $this->assertSoftDeleted('idbi_widgets', ['id' => $widgetId]);
        
        // Widget should not be found in normal queries
        $this->assertNull(Widget::find($widgetId));
        
        // Widget should be found with trashed
        $this->assertNotNull(Widget::withTrashed()->find($widgetId));
    }

    /**
     * Test widget configuration management
     */
    public function test_widget_configuration_management(): void
    {
        $config = [
            'chart_type' => 'line',
            'data_source' => 'revenue_data',
            'title' => 'Revenue Chart'
        ];
        
        $this->widgetService->updateWidgetConfiguration($this->testWidget->id, $config);
        
        // Check configuration was updated
        $chartTypeConfig = WidgetConfiguration::where('widget_id', $this->testWidget->id)
            ->where('key', 'chart_type')
            ->first();
            
        $this->assertEquals('line', $chartTypeConfig->value);
        
        // Check new configuration was added
        $titleConfig = WidgetConfiguration::where('widget_id', $this->testWidget->id)
            ->where('key', 'title')
            ->first();
            
        $this->assertNotNull($titleConfig);
        $this->assertEquals('Revenue Chart', $titleConfig->value);
    }

    /**
     * Test widget configuration retrieval
     */
    public function test_widget_configuration_retrieval(): void
    {
        $config = $this->widgetService->getWidgetConfiguration($this->testWidget->id);
        
        $this->assertIsArray($config);
        $this->assertArrayHasKey('chart_type', $config);
        $this->assertArrayHasKey('data_source', $config);
        $this->assertEquals('bar', $config['chart_type']);
        $this->assertEquals('sales_data', $config['data_source']);
    }

    /**
     * Test widget positioning
     */
    public function test_widget_positioning(): void
    {
        $newPosition = [
            'position_x' => 3,
            'position_y' => 2,
            'width' => 4,
            'height' => 3
        ];
        
        $this->widgetService->updateWidgetPosition($this->testWidget->id, $newPosition);
        
        $updatedWidget = $this->testWidget->fresh();
        $this->assertEquals(3, $updatedWidget->position_x);
        $this->assertEquals(2, $updatedWidget->position_y);
        $this->assertEquals(4, $updatedWidget->width);
        $this->assertEquals(3, $updatedWidget->height);
    }

    /**
     * Test widget ordering
     */
    public function test_widget_ordering(): void
    {
        // Create additional widgets
        $widget2 = Widget::create([
            'name' => 'second_widget',
            'dashboard_id' => $this->testDashboard->id,
            'widget_type_id' => $this->chartWidgetType->id,
            'title' => 'Second Widget',
            'description' => 'Second test widget',
            'position_x' => 6,
            'position_y' => 0,
            'width' => 6,
            'height' => 4,
            'order_index' => 2,
            'is_active' => true,
            'created_by' => $this->testUser->id
        ]);
        
        $widget3 = Widget::create([
            'name' => 'third_widget',
            'dashboard_id' => $this->testDashboard->id,
            'widget_type_id' => $this->chartWidgetType->id,
            'title' => 'Third Widget',
            'position_x' => 6,
            'position_y' => 4,
            'width' => 6,
            'height' => 4,
            'order_index' => 3,
            'is_active' => true,
            'created_by' => $this->testUser->id
        ]);
        
        // Test ordering
        $orderedWidgets = Widget::where('dashboard_id', $this->testDashboard->id)
            ->orderBy('order_index')
            ->get();
            
        $this->assertEquals('Sales Chart', $orderedWidgets->get(0)->title);
        $this->assertEquals('Second Widget', $orderedWidgets->get(1)->title);
        $this->assertEquals('Third Widget', $orderedWidgets->get(2)->title);
    }

    /**
     * Test widget visibility management
     */
    public function test_widget_visibility_management(): void
    {
        // Widget should be active initially
        $this->assertTrue($this->testWidget->is_active);
        
        // Hide widget
        $this->widgetService->hideWidget($this->testWidget->id);
        $this->assertFalse($this->testWidget->fresh()->is_active);
        
        // Show widget
        $this->widgetService->showWidget($this->testWidget->id);
        $this->assertTrue($this->testWidget->fresh()->is_active);
    }

    /**
     * Test widget rendering
     */
    public function test_widget_rendering(): void
    {
        $renderedWidget = $this->renderingService->renderWidget($this->testWidget);
        
        $this->assertIsArray($renderedWidget);
        $this->assertArrayHasKey('id', $renderedWidget);
        $this->assertArrayHasKey('type', $renderedWidget);
        $this->assertArrayHasKey('title', $renderedWidget);
        $this->assertArrayHasKey('config', $renderedWidget);
        $this->assertArrayHasKey('data', $renderedWidget);
        $this->assertArrayHasKey('position', $renderedWidget);
        
        $this->assertEquals($this->testWidget->id, $renderedWidget['id']);
        $this->assertEquals('chart', $renderedWidget['type']);
        $this->assertEquals('Sales Chart', $renderedWidget['title']);
    }

    /**
     * Test dashboard widget layout
     */
    public function test_dashboard_widget_layout(): void
    {
        $layout = $this->widgetService->getDashboardLayout($this->testDashboard->id);
        
        $this->assertIsArray($layout);
        $this->assertArrayHasKey('widgets', $layout);
        $this->assertArrayHasKey('grid_size', $layout);
        $this->assertGreaterThan(0, count($layout['widgets']));
        
        // Check widget structure in layout
        $widget = $layout['widgets'][0];
        $this->assertArrayHasKey('id', $widget);
        $this->assertArrayHasKey('position_x', $widget);
        $this->assertArrayHasKey('position_y', $widget);
        $this->assertArrayHasKey('width', $widget);
        $this->assertArrayHasKey('height', $widget);
    }

    /**
     * Test widget data source validation
     */
    public function test_widget_data_source_validation(): void
    {
        $validDataSources = ['sales_data', 'user_data', 'revenue_data'];
        
        foreach ($validDataSources as $dataSource) {
            $isValid = $this->widgetService->validateDataSource($dataSource);
            $this->assertTrue($isValid);
        }
        
        $invalidDataSource = 'invalid_data_source';
        $isValid = $this->widgetService->validateDataSource($invalidDataSource);
        $this->assertFalse($isValid);
    }

    /**
     * Test widget configuration schema validation
     */
    public function test_widget_configuration_schema_validation(): void
    {
        $validConfig = [
            'chart_type' => 'bar',
            'data_source' => 'sales_data'
        ];
        
        $isValid = $this->widgetService->validateConfiguration(
            $validConfig,
            $this->chartWidgetType
        );
        
        $this->assertTrue($isValid);
        
        $invalidConfig = [
            'chart_type' => 'bar'
            // Missing required 'data_source'
        ];
        
        $isValid = $this->widgetService->validateConfiguration(
            $invalidConfig,
            $this->chartWidgetType
        );
        
        $this->assertFalse($isValid);
    }

    /**
     * Test widget cloning
     */
    public function test_widget_cloning(): void
    {
        $clonedWidget = $this->widgetService->cloneWidget($this->testWidget->id);
        
        $this->assertInstanceOf(Widget::class, $clonedWidget);
        $this->assertNotEquals($this->testWidget->id, $clonedWidget->id);
        $this->assertEquals($this->testWidget->title . ' (Copy)', $clonedWidget->title);
        $this->assertEquals($this->testWidget->widget_type_id, $clonedWidget->widget_type_id);
        $this->assertEquals($this->testWidget->dashboard_id, $clonedWidget->dashboard_id);
        
        // Check configuration was cloned
        $originalConfig = $this->widgetService->getWidgetConfiguration($this->testWidget->id);
        $clonedConfig = $this->widgetService->getWidgetConfiguration($clonedWidget->id);
        
        $this->assertEquals($originalConfig, $clonedConfig);
    }

    /**
     * Test widget search functionality
     */
    public function test_widget_search_functionality(): void
    {
        // Create additional widgets
        Widget::create([
            'name' => 'user_analytics_table',
            'dashboard_id' => $this->testDashboard->id,
            'widget_type_id' => $this->tableWidgetType->id,
            'title' => 'User Analytics Table',
            'description' => 'Analytics for users',
            'position_x' => 0,
            'position_y' => 4,
            'width' => 12,
            'height' => 6,
            'order_index' => 2,
            'is_active' => true,
            'created_by' => $this->testUser->id
        ]);
        
        // Search by title
        $results = $this->widgetService->searchWidgets('Analytics');
        $this->assertGreaterThanOrEqual(1, $results->count());
        
        $found = false;
        foreach ($results as $widget) {
            if (str_contains($widget->title, 'Analytics')) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    /**
     * Test widget filtering by type
     */
    public function test_widget_filtering_by_type(): void
    {
        // Create table widget
        Widget::create([
            'name' => 'data_table_widget',
            'dashboard_id' => $this->testDashboard->id,
            'widget_type_id' => $this->tableWidgetType->id,
            'title' => 'Data Table',
            'position_x' => 0,
            'position_y' => 4,
            'width' => 12,
            'height' => 6,
            'order_index' => 2,
            'is_active' => true,
            'created_by' => $this->testUser->id
        ]);
        
        // Filter chart widgets
        $chartWidgets = $this->widgetService->getWidgetsByType('chart');
        $this->assertGreaterThanOrEqual(1, $chartWidgets->count());
        
        foreach ($chartWidgets as $widget) {
            $this->assertEquals('chart', $widget->widgetType->name);
        }
        
        // Filter table widgets
        $tableWidgets = $this->widgetService->getWidgetsByType('table');
        $this->assertGreaterThanOrEqual(1, $tableWidgets->count());
        
        foreach ($tableWidgets as $widget) {
            $this->assertEquals('table', $widget->widgetType->name);
        }
    }

    /**
     * Test widget performance metrics
     */
    public function test_widget_performance_metrics(): void
    {
        // Simulate widget load times
        $this->widgetService->recordWidgetLoadTime($this->testWidget->id, 150); // 150ms
        $this->widgetService->recordWidgetLoadTime($this->testWidget->id, 200); // 200ms
        $this->widgetService->recordWidgetLoadTime($this->testWidget->id, 175); // 175ms
        
        $metrics = $this->widgetService->getWidgetPerformanceMetrics($this->testWidget->id);
        
        $this->assertArrayHasKey('average_load_time', $metrics);
        $this->assertArrayHasKey('min_load_time', $metrics);
        $this->assertArrayHasKey('max_load_time', $metrics);
        $this->assertArrayHasKey('total_loads', $metrics);
        
        $this->assertEquals(175, $metrics['average_load_time']); // (150+200+175)/3
        $this->assertEquals(150, $metrics['min_load_time']);
        $this->assertEquals(200, $metrics['max_load_time']);
        $this->assertEquals(3, $metrics['total_loads']);
    }

    /**
     * Test widget caching
     */
    public function test_widget_caching(): void
    {
        // Clear cache
        $this->widgetService->clearWidgetCache($this->testWidget->id);
        
        // First render should hit database
        $widget1 = $this->renderingService->renderWidget($this->testWidget);
        
        // Second render should hit cache
        $widget2 = $this->renderingService->renderWidget($this->testWidget);
        
        $this->assertEquals($widget1, $widget2);
    }

    /**
     * Test widget export functionality
     */
    public function test_widget_export_functionality(): void
    {
        $exportData = $this->widgetService->exportWidget($this->testWidget->id);
        
        $this->assertIsArray($exportData);
        $this->assertArrayHasKey('widget', $exportData);
        $this->assertArrayHasKey('configuration', $exportData);
        $this->assertArrayHasKey('widget_type', $exportData);
        
        // Check widget data
        $this->assertEquals($this->testWidget->title, $exportData['widget']['title']);
        $this->assertEquals($this->testWidget->description, $exportData['widget']['description']);
        
        // Check configuration data
        $this->assertArrayHasKey('chart_type', $exportData['configuration']);
        $this->assertArrayHasKey('data_source', $exportData['configuration']);
    }

    /**
     * Test widget import functionality
     */
    public function test_widget_import_functionality(): void
    {
        $importData = [
            'widget' => [
                'name' => 'imported_widget',
                'title' => 'Imported Widget',
                'description' => 'Widget imported from export',
                'width' => 8,
                'height' => 6
            ],
            'widget_type' => [
                'name' => 'chart'
            ],
            'configuration' => [
                'chart_type' => 'pie',
                'data_source' => 'category_data'
            ]
        ];
        
        $importedWidget = $this->widgetService->importWidget(
            $this->testDashboard->id,
            $importData,
            $this->testUser->id
        );
        
        $this->assertInstanceOf(Widget::class, $importedWidget);
        $this->assertEquals('Imported Widget', $importedWidget->title);
        $this->assertEquals(8, $importedWidget->width);
        $this->assertEquals(6, $importedWidget->height);
        
        // Check configuration was imported
        $config = $this->widgetService->getWidgetConfiguration($importedWidget->id);
        $this->assertEquals('pie', $config['chart_type']);
        $this->assertEquals('category_data', $config['data_source']);
    }

    /**
     * Test widget access control
     */
    public function test_widget_access_control(): void
    {
        // User should have access to their own widgets
        $hasAccess = $this->widgetService->userCanAccessWidget(
            $this->testUser->id,
            $this->testWidget->id
        );
        
        $this->assertTrue($hasAccess);
        
        // Create another user
        $otherUser = User::factory()->create([
            'email' => 'other@example.com',
            'status' => 'active',
            'terms_accepted' => true
        ]);
        
        // Other user should not have access to private dashboard widgets
        $hasAccess = $this->widgetService->userCanAccessWidget(
            $otherUser->id,
            $this->testWidget->id
        );
        
        $this->assertFalse($hasAccess);
    }

    /**
     * Test widget type status management
     */
    public function test_widget_type_status_management(): void
    {
        // Widget type should be active initially
        $this->assertTrue($this->chartWidgetType->is_active);
        
        // Deactivate widget type
        $this->chartWidgetType->update(['is_active' => false]);
        $this->assertFalse($this->chartWidgetType->fresh()->is_active);
        
        // Reactivate widget type
        $this->chartWidgetType->update(['is_active' => true]);
        $this->assertTrue($this->chartWidgetType->fresh()->is_active);
    }

    /**
     * Test widget timestamps
     */
    public function test_widget_timestamps(): void
    {
        $this->assertNotNull($this->testWidget->created_at);
        $this->assertNotNull($this->testWidget->updated_at);
        $this->assertInstanceOf(Carbon::class, $this->testWidget->created_at);
        $this->assertInstanceOf(Carbon::class, $this->testWidget->updated_at);
    }

    /**
     * Clean up after tests
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }
}