# **Analytics Hub - Development Instructions**

## **1. Development Overview**

This document provides clear instructions for developing the Analytics Hub application based on the requirements document. All developers must follow these guidelines to ensure consistency and maintainability.

### **1.1 Core Principles**
- **Always check existing dependencies** in `composer.json` and `package.json` before adding new packages
- **Use comprehensive code comments** for all development work
- **Follow Laravel best practices** and conventions
- **Maintain dark theme consistency** across all UI components
- **Implement security measures** from the start, not as an afterthought
- **Complete each task fully** before marking it as done in the Task List

---

## **2. Pre-Development Checklist**

### **2.1 Environment Setup**
1. Verify Laravel version compatibility (use latest stable version)
2. Ensure PostgreSQL is installed and configured
3. Set up proper `.env` file with all required configurations
4. Configure mail settings for SMTP
5. Set up queue workers for background jobs

### **2.2 Dependency Management**
Before adding ANY new package:
1. **Check `composer.json`** for existing PHP packages
2. **Check `package.json`** for existing JavaScript packages
3. Verify if existing packages can fulfill the requirement
4. Document why a new package is necessary if adding one
5. Consider package size, maintenance status, and security

### **2.3 Task Completion Criteria**
Only mark a task as complete `[x]` when:
1. Code is written with proper comments
2. Functionality is tested
3. Documentation is updated
4. Code review is completed
5. Integration with other modules is verified

---

## **3. Database Development Instructions**

### **3.1 Table Creation Guidelines**
- All tables must use `idbi_` prefix
- Use UUID for primary keys (implement UUID trait)
- Include `created_at`, `updated_at` timestamps
- Add `deleted_at` for soft deletable models
- Create proper indexes for frequently queried columns
- Follow the exact table structure from Requirements document

### **3.2 Required Database Views**
Create these views for performance optimization:
- `v_top_active_users` - Monthly login statistics
- `v_login_trends` - 15-day login trend data
- `v_popular_content` - Most visited content
- `v_online_users` - Real-time active sessions

### **3.3 Migration Standards**
```
// Comment structure for migrations
// Purpose: [Clear description of what this migration does]
// Related Feature: [Feature/Module name]
// Dependencies: [List any dependent migrations]
```

### **3.4 Model Implementation**
- Create models with proper PHPDoc comments
- Define all relationships explicitly
- Add scopes for common queries
- Implement observers for activity logging
- Use traits for reusable functionality (UUID, SoftDeletes)

---

## **4. Module Development Order**

Follow this EXACT sequence to ensure proper dependencies:

1. **Authentication System** (Foundation)
   - User model with UUID
   - Login/logout functionality
   - Password reset mechanism
   - IP tracking and blacklisting
   - Session management with 30-minute timeout

2. **Role & Permission System**
   - Roles table and model
   - Permissions table and model
   - Role-permission pivot table
   - Middleware for authorization
   - Permission caching mechanism

3. **User Management**
   - User CRUD operations
   - Invitation system with email queue
   - Profile management
   - Avatar handling (2MB limit, 400x400px)
   - Activity logging

4. **Menu Management**
   - Hierarchical menu structure (3 levels max)
   - Role-based visibility
   - Dynamic icon support (Iconify)
   - Active state detection
   - Menu caching per role

5. **Content Management**
   - Custom HTML content with TinyMCE/CKEditor
   - Embedded content with AES-256 URL encryption
   - Content-role relationships
   - Visit tracking
   - Browser inspection protection

6. **Email Template System**
   - Template CRUD
   - Variable replacement engine
   - Queue integration
   - Default system templates
   - Retry logic (3 attempts)

7. **Notification System**
   - Real-time notifications
   - Database-driven notifications
   - Mark as read functionality
   - Bell icon with counter
   - Role-based targeting

8. **Dashboard Widgets**
   - Individual widget components
   - Auto-refresh mechanisms
   - Data aggregation queries
   - Loading states
   - Error handling

---

## **5. Frontend Development Instructions**

### **5.1 Theme Implementation**
Always use these EXACT color variables:
```
Background: #0E0E44 (Dark blue)
Primary: #FF7A00 (Orange)
Text Primary: #FFFFFF
Text Secondary: #B0B0B0
Success: #4CAF50
Warning: #FFC107
Error: #F44336
```

### **5.2 UI Component Standards**
- Use existing Laravel UI components when possible
- Check for Bootstrap or Tailwind classes before writing custom CSS
- Implement loading states for all async operations
- Add proper transitions (300ms ease)
- Ensure responsive design for desktop and tablet landscape only

### **5.3 JavaScript Development**
- Check `package.json` for existing libraries
- Use Laravel Mix for asset compilation
- Implement proper error handling
- Add loading indicators for AJAX calls
- Set up auto-refresh intervals as specified in requirements

### **5.4 Widget Refresh Intervals**
- Clock: 1 second
- Online users: 30 seconds
- Other widgets: 5 minutes
- Implement cleanup on component unmount

---

## **6. Security Implementation Instructions**

### **6.1 Authentication Security**
Implement ALL of these security measures:
1. Hash passwords using bcrypt
2. Implement CSRF protection on all forms
3. Use prepared statements for all queries
4. Validate all input data
5. Implement rate limiting
6. Track failed login attempts (30 = blacklist)
7. Implement IP blacklisting functionality
8. Add password history tracking (last 5)
9. Enforce password expiry (90 days)

### **6.2 Content Security**
- Implement AES-256 encryption for embedded URLs
- Use UUIDs for public-facing identifiers
- Add XSS protection headers
- Implement content security policies
- Prevent browser inspection of embedded content

### **6.3 Session Management**
- Configure session timeout (30 minutes)
- Implement secure session cookies
- Add session invalidation on logout
- Track concurrent sessions
- Implement session fingerprinting

---

## **7. Feature Implementation Guidelines**

### **7.1 Email System**
1. Use Laravel's built-in mail system
2. Implement queues for all email sending
3. Add retry logic for failed emails (3 attempts)
4. Log all email activities
5. Implement 30-second cooldown for password reset
6. Create all default templates as specified

### **7.2 File Upload Handling**
For user avatars:
- Validate file types (JPG, PNG only)
- Implement size restrictions (2MB max)
- Auto-resize to 400x400px
- Store in `storage/app/public/avatars`
- Implement cropping functionality
- Provide default avatars

### **7.3 Real-time Features**
For widgets requiring auto-refresh:
- Use JavaScript intervals with proper cleanup
- Implement exponential backoff for failed requests
- Add visual indicators for data freshness
- Handle connection failures gracefully

### **7.4 Terms & Conditions**
- Force acceptance on first login
- Log acceptance with timestamp
- Implement modal that cannot be dismissed
- Update user record upon acceptance

---

## **8. Code Comment Standards**

### **8.1 Controller Methods**
Every controller method must have:
- Purpose description
- Parameter documentation
- Return type
- Security measures implemented
- Caching strategy if applicable

### **8.2 Model Relationships**
Document:
- Relationship type
- Related table/model
- Eager loading recommendations
- Any special conditions

### **8.3 Complex Queries**
Include:
- Query purpose
- Performance considerations
- Cache key if cached
- Index usage

### **8.4 Middleware**
Document:
- Purpose
- When it runs
- What it checks
- Failure behavior

---

## **9. Testing Instructions**

### **9.1 Test Coverage Requirements**
- Authentication flows: 100% coverage
- Permission checks: All endpoints tested
- Email sending: Mock SMTP in tests
- File uploads: Test size and type validations
- Widget data: Test all aggregation queries

### **9.2 Test Data**
Create seeders for:
- System administrator account
- Sample roles (all 6 types as specified)
- Test menus (3 levels deep)
- Sample content entries
- Test users with various states

### **9.3 Test Scenarios**
Must test:
- First-time login flow
- Password reset flow
- Failed login attempts
- IP blacklisting
- Session timeout
- Concurrent sessions
- File upload limits
- Email queue processing

---

## **10. Performance Optimization Guidelines**

### **10.1 Query Optimization**
- Always use eager loading for relationships
- Implement query result caching
- Use database views for complex reports
- Add indexes for foreign keys and frequently searched columns
- Batch operations where possible

### **10.2 Asset Optimization**
- Minify CSS and JavaScript in production
- Use Laravel Mix versioning for cache busting
- Lazy load images where appropriate
- Implement browser caching headers
- Optimize widget refresh to prevent overlap

### **10.3 Caching Strategy**
Cache with these TTLs:
- Menu structure per role: 1 hour
- User permissions: 30 minutes
- Widget data: Varies (1-60 minutes)
- Static content: 1 week
- Clear relevant caches on updates

---

## **11. Business Logic Implementation**

### **11.1 User States**
Implement state transitions as per Logic document:
- Guest → Pending → Authenticated
- Handle Suspended and Expired states
- Implement proper redirects for each state

### **11.2 Password Policy**
Enforce ALL requirements:
- Minimum 8 characters
- Mixed case required
- Numbers required
- Special characters required
- No reuse of last 5 passwords
- 90-day expiry
- Force change on first login

### **11.3 Content Access**
Follow the exact flow from Logic document:
1. Check menu permissions
2. Retrieve content
3. Check content type
4. Handle accordingly (HTML or embedded)
5. Log visit

---

## **12. Integration Requirements**

### **12.1 SMTP Configuration**
- Test connection before sending
- Handle connection failures
- Implement fallback options
- Log all SMTP errors
- Monitor delivery rates

### **12.2 External Services**
- Implement timeout handling (30 seconds)
- Add retry logic with exponential backoff
- Cache successful responses
- Log all external API calls
- Handle service unavailability

---

## **13. Development Workflow**

### **13.1 Daily Development Process**
1. Check Task List for next uncompleted task
2. Review related Requirements section
3. Review Logic document for implementation details
4. Check for existing packages before adding new ones
5. Implement with comprehensive comments
6. Test thoroughly
7. Update documentation
8. Mark task as complete only when ALL criteria are met

### **13.2 Code Review Checklist**
Before marking any task complete, verify:
- [ ] All code has appropriate comments
- [ ] No new packages added without justification
- [ ] Security measures implemented as specified
- [ ] Database queries optimized
- [ ] UI follows dark theme exactly
- [ ] Tests cover main functionality
- [ ] Error handling in place
- [ ] Documentation updated
- [ ] Integration with other modules verified
- [ ] Performance considerations addressed

### **13.3 Progress Tracking**
- Update Task List daily
- Document any blockers
- Note any deviations from requirements
- Keep track of technical debt
- Monitor test coverage

---

## **14. Critical Implementation Notes**

### **14.1 Never Skip These**
1. UUID implementation for all primary keys
2. IP blacklisting after 30 failed attempts
3. Password history tracking
4. T&C acceptance on first login
5. Session timeout at 30 minutes
6. Email queue implementation
7. Content URL encryption
8. Activity logging for all actions

### **14.2 Common Mistakes to Avoid**
1. Don't use incremental IDs - use UUIDs
2. Don't send emails synchronously - use queues
3. Don't skip validation - validate everything
4. Don't ignore rate limiting
5. Don't hardcode configurations
6. Don't skip error handling
7. Don't ignore performance from the start
8. Don't mark tasks complete prematurely

### **14.3 Quality Standards**
- Every function must have a comment explaining its purpose
- Every database query must consider performance
- Every user input must be validated
- Every external call must have error handling
- Every UI component must follow the theme
- Every feature must have tests
- Every module must integrate properly with others

Remember: **Follow the Task List sequentially**. Each task builds upon previous ones. Skipping ahead will cause integration
