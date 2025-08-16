# Organization Management System - Deployment Guide

## Overview

This document provides comprehensive instructions for deploying the Organization Management System for the Getlancer Quote platform. The system includes Saudi Arabian business compliance features, document verification, user management, and settings configuration.

## System Components

### 1. Database Schema
- **File**: `sql/organization_schema.sql`
- **Tables**: 
  - `organizations` - Main organization data with Saudi compliance fields
  - `organization_attachments` - Document management
  - `organization_users` - User-organization relationships
  - `organization_verifications` - Compliance verification tracking
  - `organization_settings` - Configuration management

### 2. Models
- **Organization.php** - Main organization model with validation and business logic
- **OrganizationAttachment.php** - Document management with security features
- **OrganizationUser.php** - User management with role-based permissions
- **OrganizationVerification.php** - Compliance verification workflows
- **OrganizationSetting.php** - Configuration management with encryption support

### 3. API Layer
- **OrganizationAPI.php** - Comprehensive API endpoints
- **routes/organizations.php** - Slim framework route definitions

## Deployment Steps

### Step 1: Database Setup

1. **Execute the schema file**:
   ```sql
   -- Run the organization schema
   \i sql/organization_schema.sql
   ```

2. **Verify table creation**:
   ```sql
   -- Check if tables were created successfully
   SELECT table_name FROM information_schema.tables 
   WHERE table_schema = 'public' 
   AND table_name LIKE 'organization%';
   ```

3. **Apply performance indexes**:
   ```sql
   -- Additional indexes for performance
   CREATE INDEX CONCURRENTLY idx_organizations_verification_status 
   ON organizations(is_verified, is_active);
   
   CREATE INDEX CONCURRENTLY idx_organization_users_active_role 
   ON organization_users(organization_id, is_active, role);
   
   CREATE INDEX CONCURRENTLY idx_organization_attachments_type_verified 
   ON organization_attachments(organization_id, attachment_type, is_verified);
   ```

### Step 2: Model Integration

1. **Ensure AppModel is available**:
   ```php
   // Verify server/php/Slim/lib/Models/AppModel.php exists and is functional
   ```

2. **Update autoloader** (if needed):
   ```php
   // Add to composer.json or autoload configuration
   "Models\\": "server/php/Slim/lib/Models/"
   ```

3. **Test model functionality**:
   ```php
   // Test basic model operations
   $org = new Models\Organization();
   $validation = $org->validate([
       'organization_name' => 'Test Organization',
       'organization_type' => 'Buyer',
       // ... other required fields
   ]);
   ```

### Step 3: API Integration

1. **Include routes in main application**:
   ```php
   // In server/php/Slim/public/index.php or main routes file
   require_once __DIR__ . '/../lib/routes/organizations.php';
   ```

2. **Update ACL permissions**:
   ```php
   // Add organization permissions to your ACL system
   $organizationPermissions = [
       'canListOrganizations',
       'canCreateOrganization',
       'canViewOrganization',
       'canUpdateOrganization',
       'canDeleteOrganization',
       'canViewOrganizationAttachments',
       'canUploadOrganizationAttachment',
       'canVerifyOrganizationAttachment',
       'canViewOrganizationUsers',
       'canAddOrganizationUser',
       'canUpdateOrganizationUserRole',
       'canViewOrganizationSettings',
       'canUpdateOrganizationSettings',
       'canViewPendingOrganizations',
       'canApproveOrganization',
       'canRejectOrganization',
       'canViewOrganizationStats',
       'canViewOrganizationVerificationStatus'
   ];
   ```

3. **Configure file upload security**:
   ```php
   // Ensure FileUploadSecurity class is available
   // Update upload paths and security settings
   define('ORGANIZATION_UPLOAD_PATH', 'media/Organization/');
   define('MAX_ORGANIZATION_FILE_SIZE', 10 * 1024 * 1024); // 10MB
   ```

### Step 4: Security Configuration

1. **File upload directory setup**:
   ```bash
   # Create upload directories with proper permissions
   mkdir -p media/Organization
   chmod 755 media/Organization
   chown www-data:www-data media/Organization
   ```

2. **Security headers**:
   ```apache
   # Add to .htaccess in media/Organization/
   <Files "*">
       Order Deny,Allow
       Deny from all
   </Files>
   
   <FilesMatch "\.(pdf|jpg|jpeg|png|doc|docx)$">
       Order Allow,Deny
       Allow from all
   </FilesMatch>
   ```

3. **Database security**:
   ```sql
   -- Create dedicated database user for organization operations
   CREATE USER org_manager WITH PASSWORD 'secure_password';
   GRANT SELECT, INSERT, UPDATE ON organizations TO org_manager;
   GRANT SELECT, INSERT, UPDATE, DELETE ON organization_attachments TO org_manager;
   GRANT SELECT, INSERT, UPDATE, DELETE ON organization_users TO org_manager;
   GRANT SELECT, INSERT, UPDATE ON organization_verifications TO org_manager;
   GRANT SELECT, INSERT, UPDATE, DELETE ON organization_settings TO org_manager;
   ```

### Step 5: Configuration

1. **Environment variables**:
   ```bash
   # Add to .env file
   ORGANIZATION_VAT_VALIDATION=true
   ORGANIZATION_CR_VALIDATION=true
   ORGANIZATION_AUTO_APPROVE=false
   ORGANIZATION_REQUIRE_VERIFICATION=true
   ORGANIZATION_MAX_USERS_DEFAULT=50
   ORGANIZATION_MAX_STORAGE_MB=5120
   ```

2. **Application constants**:
   ```php
   // Add to constants file
   define('ORGANIZATION_VAT_REGEX', '/^3[0-9]{14}$/');
   define('ORGANIZATION_CR_REGEX', '/^[0-9]{10}$/');
   define('ORGANIZATION_NATIONAL_ADDRESS_REGEX', '/^[0-9]{8}$/');
   ```

## Testing

### Unit Tests

1. **Model validation tests**:
   ```php
   // Test Saudi VAT number validation
   $org = new Organization();
   $result = $org->validate(['vat_number' => '315123456789012']);
   assert(empty($result)); // Should pass
   
   $result = $org->validate(['vat_number' => '123456789012345']);
   assert(!empty($result)); // Should fail
   ```

2. **API endpoint tests**:
   ```bash
   # Test organization creation
   curl -X POST http://localhost/api/organizations \
     -H "Content-Type: application/json" \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -d '{
       "organization_name": "Test Saudi Company",
       "organization_type": "Buyer",
       "vat_number": "315123456789012",
       "cr_number": "1234567890",
       "national_address": "12345678",
       "city": "Riyadh",
       "state_province": "Riyadh Province",
       "country": "Saudi Arabia",
       "contact_email": "test@company.sa",
       "contact_phone": "+966501234567",
       "address_line1": "King Fahd Road, Olaya District"
     }'
   ```

### Integration Tests

1. **File upload test**:
   ```bash
   # Test document upload
   curl -X POST http://localhost/api/organizations/1/attachments \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -F "file=@test_vat_certificate.pdf" \
     -F "attachment_type=VAT"
   ```

2. **User management test**:
   ```bash
   # Test adding user to organization
   curl -X POST http://localhost/api/organizations/1/users \
     -H "Content-Type: application/json" \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -d '{
       "user_id": 2,
       "role": "manager",
       "permissions": ["manage_documents", "view_reports"]
     }'
   ```

## Monitoring and Maintenance

### Performance Monitoring

1. **Database queries**:
   ```sql
   -- Monitor slow queries
   SELECT query, mean_time, calls 
   FROM pg_stat_statements 
   WHERE query LIKE '%organization%' 
   ORDER BY mean_time DESC;
   ```

2. **File storage monitoring**:
   ```bash
   # Monitor upload directory size
   du -sh media/Organization/
   
   # Monitor file count
   find media/Organization/ -type f | wc -l
   ```

### Backup Strategy

1. **Database backup**:
   ```bash
   # Backup organization tables
   pg_dump -t organizations -t organization_* your_database > org_backup.sql
   ```

2. **File backup**:
   ```bash
   # Backup uploaded documents
   tar -czf org_files_backup.tar.gz media/Organization/
   ```

### Security Auditing

1. **Regular security checks**:
   ```sql
   -- Check for unverified organizations
   SELECT id, organization_name, created_at 
   FROM organizations 
   WHERE is_verified = false 
   AND created_at < NOW() - INTERVAL '30 days';
   
   -- Check for suspicious file uploads
   SELECT oa.*, o.organization_name 
   FROM organization_attachments oa
   JOIN organizations o ON oa.organization_id = o.id
   WHERE oa.is_verified = false 
   AND oa.uploaded_at < NOW() - INTERVAL '7 days';
   ```

2. **Access log monitoring**:
   ```bash
   # Monitor API access patterns
   grep "organizations" /var/log/apache2/access.log | \
   awk '{print $1, $7}' | sort | uniq -c | sort -nr
   ```

## Troubleshooting

### Common Issues

1. **File upload failures**:
   - Check directory permissions
   - Verify file size limits
   - Check available disk space
   - Validate file types

2. **Validation errors**:
   - Verify Saudi compliance regex patterns
   - Check required field configurations
   - Validate unique constraints

3. **Permission issues**:
   - Check ACL configuration
   - Verify user roles and permissions
   - Check organization membership

### Error Codes

- **1001**: Invalid VAT number format
- **1002**: Invalid CR number format
- **1003**: Invalid national address format
- **1004**: Organization name already exists
- **1005**: User already exists in organization
- **1006**: Insufficient permissions
- **1007**: File upload security violation
- **1008**: Document verification failed

## Support and Documentation

### API Documentation

The organization API endpoints follow RESTful conventions:

- `GET /organizations` - List organizations
- `POST /organizations` - Create organization
- `GET /organizations/{id}` - Get organization details
- `PUT /organizations/{id}` - Update organization
- `DELETE /organizations/{id}` - Delete organization
- `POST /organizations/{id}/attachments` - Upload document
- `GET /organizations/{id}/users` - List organization users
- `POST /organizations/{id}/users` - Add user to organization

### Contact Information

For technical support or questions about the Organization Management System:

- **Development Team**: development@getlancer.com
- **System Administrator**: admin@getlancer.com
- **Documentation**: https://docs.getlancer.com/organizations

## Changelog

### Version 1.0.0 (Current)
- Initial implementation of Organization Management System
- Saudi Arabian business compliance features
- Document verification workflows
- User management with role-based permissions
- Settings management with encryption support
- Comprehensive API endpoints
- Security enhancements and file upload protection

---

**Note**: This system is designed specifically for Saudi Arabian business compliance but can be adapted for other regions by modifying the validation rules and compliance requirements.