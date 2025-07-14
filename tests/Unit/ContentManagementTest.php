<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Content;
use App\Models\ContentVersion;
use App\Models\ContentAccessLog;
use App\Models\ContentRole;
use App\Models\User;
use App\Models\Role;
use App\Models\UserRole;
use App\Services\ContentEncryptionService;
use App\Services\ContentVisitTracker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Class ContentManagementTest
 * 
 * Unit tests for content management functionality including content CRUD,
 * encryption/decryption, versioning, and access tracking.
 * 
 * @package Tests\Unit
 */
class ContentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Content $testContent;
    protected Content $embeddedContent;
    protected User $testUser;
    protected Role $testRole;
    protected ContentEncryptionService $encryptionService;
    protected ContentVisitTracker $visitTracker;

    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->setUpRoleAndUser();
        $this->setUpContent();
        $this->setUpServices();
    }

    /**
     * Set up test role and user
     */
    private function setUpRoleAndUser(): void
    {
        $this->testRole = Role::create([
            'name' => 'content_viewer',
            'display_name' => 'Content Viewer',
            'description' => 'Can view content'
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
     * Set up test content
     */
    private function setUpContent(): void
    {
        $this->testContent = Content::create([
            'title' => 'Test Custom Content',
            'slug' => 'test-custom-content',
            'content' => '<h1>Test Content</h1><p>This is test content.</p>',
            'type' => 'page',
            'status' => 'published',
            'published_at' => now(),
            'author_id' => $this->testUser->id
        ]);
        
        $this->embeddedContent = Content::create([
            'title' => 'Test Embedded Content',
            'slug' => 'test-embedded-content',
            'content' => 'https://app.powerbi.com/view?r=test123',
            'type' => 'page',
            'status' => 'published',
            'published_at' => now(),
            'author_id' => $this->testUser->id
        ]);
        
        // Assign content to role
        ContentRole::create([
            'content_id' => $this->testContent->id,
            'role_id' => $this->testRole->id,
            'can_view' => true,
            'can_edit' => false,
            'assigned_at' => Carbon::now(),
            'assigned_by' => $this->testUser->id
        ]);
        
        ContentRole::create([
            'content_id' => $this->embeddedContent->id,
            'role_id' => $this->testRole->id,
            'can_view' => true,
            'can_edit' => false,
            'assigned_at' => Carbon::now(),
            'assigned_by' => $this->testUser->id
        ]);
    }

    /**
     * Set up services
     */
    private function setUpServices(): void
    {
        $this->encryptionService = new ContentEncryptionService();
        $this->visitTracker = new ContentVisitTracker();
    }

    /**
     * Test content creation
     */
    public function test_content_creation(): void
    {
        $contentData = [
            'title' => 'New Test Content',
            'slug' => 'new-test-content',
            'content_type' => 'custom',
            'content' => '<p>New content body</p>',
            'status' => 'draft'
        ];
        
        $content = Content::create($contentData);
        
        $this->assertInstanceOf(Content::class, $content);
        $this->assertEquals('New Test Content', $content->title);
        $this->assertEquals('new-test-content', $content->slug);
        $this->assertEquals('custom', $content->content_type);
        $this->assertEquals('draft', $content->status);
        $this->assertTrue(Str::isUuid($content->id));
    }

    /**
     * Test content update
     */
    public function test_content_update(): void
    {
        $originalTitle = $this->testContent->title;
        $newTitle = 'Updated Test Content';
        
        $this->testContent->update(['title' => $newTitle]);
        
        $this->assertEquals($newTitle, $this->testContent->fresh()->title);
        $this->assertNotEquals($originalTitle, $this->testContent->fresh()->title);
    }

    /**
     * Test content soft delete
     */
    public function test_content_soft_delete(): void
    {
        $contentId = $this->testContent->id;
        
        $this->testContent->delete();
        
        // Content should be soft deleted
        $this->assertSoftDeleted('idbi_contents', ['id' => $contentId]);
        
        // Content should not be found in normal queries
        $this->assertNull(Content::find($contentId));
        
        // Content should be found with trashed
        $this->assertNotNull(Content::withTrashed()->find($contentId));
    }

    /**
     * Test content status management
     */
    public function test_content_status_management(): void
    {
        // Test draft status
        $this->testContent->update(['status' => 'draft']);
        $this->assertEquals('draft', $this->testContent->fresh()->status);
        $this->assertFalse($this->testContent->fresh()->isPublished());
        
        // Test published status
        $this->testContent->update([
            'status' => 'published',
            'published_at' => now()
        ]);
        $this->assertEquals('published', $this->testContent->fresh()->status);
        $this->assertTrue($this->testContent->fresh()->isPublished());
        
        // Test archived status
        $this->testContent->update(['status' => 'archived']);
        $this->assertEquals('archived', $this->testContent->fresh()->status);
        $this->assertFalse($this->testContent->fresh()->isPublished());
    }

    /**
     * Test URL encryption for embedded content
     */
    public function test_url_encryption(): void
    {
        $originalUrl = 'https://app.powerbi.com/view?r=test123';
        
        // Encrypt URL
        $encryptedUrl = $this->encryptionService->encryptUrl($originalUrl);
        $this->assertNotEquals($originalUrl, $encryptedUrl);
        $this->assertNotEmpty($encryptedUrl);
        
        // Decrypt URL
        $decryptedUrl = $this->encryptionService->decryptUrl($encryptedUrl);
        $this->assertEquals($originalUrl, $decryptedUrl);
    }

    /**
     * Test UUID-based URL masking
     */
    public function test_url_masking(): void
    {
        $originalUrl = 'https://app.powerbi.com/view?r=test123';
        
        // Generate masked URL
        $maskedUrl = $this->encryptionService->generateMaskedUrl($originalUrl);
        
        $this->assertNotEquals($originalUrl, $maskedUrl);
        $this->assertTrue(Str::isUuid(basename($maskedUrl)));
    }

    /**
     * Test content versioning
     */
    public function test_content_versioning(): void
    {
        $originalContent = $this->testContent->content;
        $newContent = '<h1>Updated Content</h1><p>This is updated content.</p>';
        
        // Create version before update
        ContentVersion::create([
            'content_id' => $this->testContent->id,
            'version_number' => 1,
            'title' => $this->testContent->title,
            'content' => $originalContent,
            'created_by' => $this->testUser->id
        ]);
        
        // Update content
        $this->testContent->update(['content' => $newContent]);
        
        // Check version exists
        $version = ContentVersion::where('content_id', $this->testContent->id)->first();
        $this->assertNotNull($version);
        $this->assertEquals($originalContent, $version->content);
        $this->assertEquals(1, $version->version_number);
    }

    /**
     * Test content access logging
     */
    public function test_content_access_logging(): void
    {
        $this->visitTracker->trackVisit(
            $this->testContent->id,
            $this->testUser->id,
            '192.168.1.1',
            'Test Browser'
        );
        
        $accessLog = ContentAccessLog::where('content_id', $this->testContent->id)
            ->where('user_id', $this->testUser->id)
            ->first();
            
        $this->assertNotNull($accessLog);
        $this->assertEquals($this->testContent->id, $accessLog->content_id);
        $this->assertEquals($this->testUser->id, $accessLog->user_id);
        $this->assertEquals('192.168.1.1', $accessLog->ip_address);
    }

    /**
     * Test content role assignment
     */
    public function test_content_role_assignment(): void
    {
        $newRole = Role::create([
            'name' => 'manager',
            'display_name' => 'Manager',
            'description' => 'Manager role'
        ]);
        
        // Assign content to new role
        ContentRole::create([
            'content_id' => $this->testContent->id,
            'role_id' => $newRole->id,
            'can_view' => true,
            'can_edit' => false,
            'assigned_at' => Carbon::now(),
            'assigned_by' => $this->testUser->id
        ]);
        
        $this->assertTrue($this->testContent->roles->contains('id', $newRole->id));
        
        // Remove role assignment
        ContentRole::where('content_id', $this->testContent->id)
                  ->where('role_id', $newRole->id)
                  ->delete();
        
        $this->assertFalse($this->testContent->fresh()->roles->contains('id', $newRole->id));
    }

    /**
     * Test content access control
     */
    public function test_content_access_control(): void
    {
        // User with assigned role should have access
        $this->assertTrue($this->testUser->canAccessContent($this->testContent));
        
        // Create user without role
        $unauthorizedUser = User::factory()->create([
            'email' => 'unauthorized@example.com',
            'status' => 'active',
            'terms_accepted' => true
        ]);
        
        // User without role should not have access
        $this->assertFalse($unauthorizedUser->canAccessContent($this->testContent));
    }

    /**
     * Test content search functionality
     */
    public function test_content_search(): void
    {
        // Create additional test content
        Content::create([
            'title' => 'Analytics Dashboard',
            'slug' => 'analytics-dashboard',
            'content_type' => 'custom',
            'content' => '<p>Analytics content</p>',
            'status' => 'published'
        ]);
        
        Content::create([
            'title' => 'Sales Report',
            'slug' => 'sales-report',
            'content_type' => 'embedded',
            'embedded_url' => 'https://example.com/sales',
            'status' => 'published'
        ]);
        
        // Search by title
        $results = Content::where('title', 'LIKE', '%Analytics%')->get();
        $this->assertEquals(1, $results->count());
        $this->assertEquals('Analytics Dashboard', $results->first()->title);
        
        // Search by content type
        $embeddedContent = Content::where('content_type', 'embedded')->get();
        $this->assertGreaterThanOrEqual(2, $embeddedContent->count());
    }

    /**
     * Test content filtering by status
     */
    public function test_content_filtering_by_status(): void
    {
        // Create content with different statuses
        Content::create([
            'title' => 'Draft Content',
            'slug' => 'draft-content',
            'content_type' => 'custom',
            'content' => '<p>Draft content</p>',
            'status' => 'draft'
        ]);
        
        Content::create([
            'title' => 'Archived Content',
            'slug' => 'archived-content',
            'content_type' => 'custom',
            'content' => '<p>Archived content</p>',
            'status' => 'archived'
        ]);
        
        // Filter published content
        $publishedContent = Content::where('status', 'published')->get();
        $this->assertGreaterThanOrEqual(2, $publishedContent->count());
        
        // Filter draft content
        $draftContent = Content::where('status', 'draft')->get();
        $this->assertEquals(1, $draftContent->count());
        
        // Filter archived content
        $archivedContent = Content::where('status', 'archived')->get();
        $this->assertEquals(1, $archivedContent->count());
    }

    /**
     * Test content slug uniqueness
     */
    public function test_content_slug_uniqueness(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        // Try to create content with existing slug
        Content::create([
            'title' => 'Duplicate Content',
            'slug' => $this->testContent->slug, // Same slug as existing content
            'content_type' => 'custom',
            'content' => '<p>Duplicate content</p>',
            'status' => 'draft'
        ]);
    }

    /**
     * Test content expiry functionality
     */
    public function test_content_expiry(): void
    {
        // Create content with expiry date
        $expiringContent = Content::create([
            'title' => 'Expiring Content',
            'slug' => 'expiring-content',
            'content_type' => 'custom',
            'content' => '<p>This content will expire</p>',
            'status' => 'published',
            'expires_at' => now()->addDays(7)
        ]);
        
        // Content should not be expired yet
        $this->assertFalse($expiringContent->isExpired());
        
        // Set expiry date to past
        $expiringContent->update(['expires_at' => now()->subDays(1)]);
        
        // Content should now be expired
        $this->assertTrue($expiringContent->fresh()->isExpired());
    }

    /**
     * Test content visit statistics
     */
    public function test_content_visit_statistics(): void
    {
        // Track multiple visits
        for ($i = 0; $i < 5; $i++) {
            $request = Request::create('/test', 'GET');
            $request->server->set('REMOTE_ADDR', '192.168.1.' . ($i + 1));
            $request->server->set('HTTP_USER_AGENT', 'Test Browser');
            
            $this->actingAs($this->testUser);
            $this->visitTracker->trackVisit($this->testContent, $request);
        }
        
        $visitCount = ContentAccessLog::where('content_id', $this->testContent->id)->count();
        $this->assertEquals(5, $visitCount);
        
        // Test unique visitor count
        $uniqueVisitors = ContentAccessLog::where('content_id', $this->testContent->id)
            ->distinct('user_id')
            ->count('user_id');
        $this->assertEquals(1, $uniqueVisitors);
    }

    /**
     * Test content custom fields storage
     */
    public function test_content_custom_fields_storage(): void
    {
        $customFields = [
            'author' => 'Test Author',
            'category' => 'Analytics',
            'tags' => ['dashboard', 'reports', 'analytics']
        ];
        
        $this->testContent->update(['custom_fields' => $customFields]);
        
        $savedCustomFields = $this->testContent->fresh()->custom_fields;
        $this->assertEquals($customFields, $savedCustomFields);
    }

    /**
     * Test content timestamps
     */
    public function test_content_timestamps(): void
    {
        $this->assertNotNull($this->testContent->created_at);
        $this->assertNotNull($this->testContent->updated_at);
        $this->assertInstanceOf(Carbon::class, $this->testContent->created_at);
        $this->assertInstanceOf(Carbon::class, $this->testContent->updated_at);
        
        // Test published_at timestamp
        $this->assertNotNull($this->testContent->published_at);
        $this->assertInstanceOf(Carbon::class, $this->testContent->published_at);
    }

    /**
     * Test content ordering
     */
    public function test_content_ordering(): void
    {
        // Create content with different order indices
        $content1 = Content::create([
            'title' => 'First Content',
            'slug' => 'first-content',
            'content_type' => 'custom',
            'content' => '<p>First</p>',
            'status' => 'published',
            'order_index' => 1
        ]);
        
        $content2 = Content::create([
            'title' => 'Second Content',
            'slug' => 'second-content',
            'content_type' => 'custom',
            'content' => '<p>Second</p>',
            'status' => 'published',
            'order_index' => 2
        ]);
        
        // Test ordering
        $orderedContent = Content::orderBy('order_index')->get();
        $this->assertEquals('First Content', $orderedContent->first()->title);
        $this->assertEquals('Second Content', $orderedContent->skip(1)->first()->title);
    }

    /**
     * Clean up after tests
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }
}