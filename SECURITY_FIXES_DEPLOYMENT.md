# Security Fixes and Modernization Deployment Guide

## Overview

This document outlines the critical security fixes and modernization updates implemented for the Getlancer Quote platform. These changes address major security vulnerabilities, performance issues, and technical debt.

## Critical Security Fixes Implemented

### 1. File Upload Security (`FileUploadSecurity.php`)

**Issues Fixed:**
- File extension validation bypass
- MIME type spoofing attacks
- Path traversal vulnerabilities
- Malicious file execution

**New Features:**
- Comprehensive file validation
- Content-based file type detection
- Secure filename generation
- Directory traversal prevention
- Malicious content scanning

**Deployment Steps:**
```bash
# 1. Copy the new security class
cp server/php/lib/FileUploadSecurity.php /path/to/production/server/php/lib/

# 2. Update file permissions
chmod 644 server/php/lib/FileUploadSecurity.php

# 3. Create secure upload directories
mkdir -p media/secure_uploads
chmod 755 media/secure_uploads

# 4. Add .htaccess protection
echo "Options -Indexes" > media/.htaccess
echo "<Files \"*.php\">" >> media/.htaccess
echo "    Require all denied" >> media/.htaccess
echo "</Files>" >> media/.htaccess
```

### 2. Secure Image Processing (`image_secure.php`)

**Issues Fixed:**
- SQL injection in image queries
- Path traversal in file access
- Memory exhaustion attacks
- Cache poisoning

**New Features:**
- PDO prepared statements
- Input validation and sanitization
- Secure caching mechanism
- Memory-efficient processing
- Rate limiting protection

**Deployment Steps:**
```bash
# 1. Deploy new image processor
cp server/php/image_secure.php /path/to/production/server/php/

# 2. Create cache directory
mkdir -p cache/images
chmod 755 cache/images

# 3. Update web server configuration to use new processor
# In Apache .htaccess or Nginx config:
# RewriteRule ^image/(.*)$ image_secure.php?$1 [QSA,L]

# 4. Test image processing
curl "https://yourdomain.com/image_secure.php?model=UserAvatar&size=normal_thumb&filename=1.hash.jpg"
```

### 3. Secure Download Handler (`download_secure.php`)

**Issues Fixed:**
- Direct file access vulnerabilities
- Missing access control
- Path traversal attacks
- Information disclosure

**New Features:**
- Authentication-based access control
- Secure file serving
- Download logging
- Rate limiting
- Content-Type validation

**Deployment Steps:**
```bash
# 1. Deploy secure download handler
cp server/php/download_secure.php /path/to/production/server/php/

# 2. Update download links in application
# Replace: download.php?model=X&id=Y&filename=Z
# With: download_secure.php?model=X&id=Y&filename=Z

# 3. Test download functionality
curl -H "Authorization: Bearer YOUR_TOKEN" \
     "https://yourdomain.com/download_secure.php?model=ProjectDocument&id=1&filename=hash.pdf"
```

### 4. Enhanced API Security (`index.php` updates)

**Issues Fixed:**
- Insecure file upload handling
- Missing input validation
- Weak error handling

**New Features:**
- Secure file upload validation
- Enhanced error handling
- Input sanitization
- Rate limiting integration

## Performance Optimizations

### 1. Database Indexes (`performance_indexes.sql`)

**Improvements:**
- Added 50+ strategic indexes
- Composite indexes for complex queries
- Partial indexes for filtered queries
- Full-text search indexes

**Deployment Steps:**
```bash
# 1. Backup database
pg_dump -h localhost -U username -d database_name > backup_$(date +%Y%m%d).sql

# 2. Apply indexes (use CONCURRENTLY to avoid locks)
psql -h localhost -U username -d database_name -f sql/performance_indexes.sql

# 3. Monitor index usage
psql -h localhost -U username -d database_name -c "
SELECT schemaname, tablename, indexname, idx_scan, idx_tup_read, idx_tup_fetch 
FROM pg_stat_user_indexes 
ORDER BY idx_scan DESC;"

# 4. Analyze tables
psql -h localhost -U username -d database_name -c "ANALYZE;"
```

### 2. Security Configuration (`SecurityConfig.php`)

**Features:**
- Password strength validation
- Rate limiting
- CSRF protection
- Security headers
- Session security
- Input sanitization
- Suspicious activity detection

**Deployment Steps:**
```bash
# 1. Deploy security configuration
cp server/php/lib/SecurityConfig.php /path/to/production/server/php/lib/

# 2. Create logs directory
mkdir -p logs
chmod 755 logs

# 3. Initialize security in bootstrap
# Add to bootstrap.php:
# require_once 'lib/SecurityConfig.php';
# SecurityConfig::setSecurityHeaders();
# SecurityConfig::configureSecureSession();
```

## Modernization Updates

### 1. Updated Dependencies (`package_updated.json`)

**Major Updates:**
- Node.js 18+ requirement
- Modern security packages
- Updated build tools
- Enhanced testing framework
- Security linting tools

**Migration Steps:**
```bash
# 1. Backup current package.json
cp package.json package.json.backup

# 2. Install updated dependencies
cp package_updated.json package.json
npm install

# 3. Update build scripts
npm run build

# 4. Run security audit
npm audit
npm audit fix

# 5. Run tests
npm test
```

## Security Testing Checklist

### Pre-Deployment Testing

- [ ] File upload security tests
- [ ] SQL injection prevention tests
- [ ] XSS protection verification
- [ ] CSRF token validation
- [ ] Rate limiting functionality
- [ ] Authentication bypass tests
- [ ] Path traversal prevention
- [ ] Input validation tests

### Post-Deployment Verification

```bash
# 1. Test file upload security
curl -X POST -F "file=@malicious.php" \
     "https://yourdomain.com/api/v1/attachments?class=UserAvatar"

# 2. Test SQL injection protection
curl "https://yourdomain.com/image_secure.php?model=UserAvatar'; DROP TABLE users; --"

# 3. Test rate limiting
for i in {1..10}; do
  curl "https://yourdomain.com/api/v1/users/login" \
       -d "username=test&password=wrong"
done

# 4. Test CSRF protection
curl -X POST "https://yourdomain.com/api/v1/users" \
     -d "username=test&email=test@example.com" \
     -H "Content-Type: application/json"

# 5. Verify security headers
curl -I "https://yourdomain.com/"
```

## Performance Monitoring

### Database Performance

```sql
-- Monitor slow queries
SELECT query, mean_time, calls, total_time
FROM pg_stat_statements
ORDER BY mean_time DESC
LIMIT 10;

-- Check index usage
SELECT schemaname, tablename, indexname, idx_scan
FROM pg_stat_user_indexes
WHERE idx_scan = 0;

-- Monitor table sizes
SELECT schemaname, tablename, 
       pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) as size
FROM pg_tables
WHERE schemaname = 'public'
ORDER BY pg_total_relation_size(schemaname||'.'||tablename) DESC;
```

### Application Performance

```bash
# Monitor file upload performance
time curl -X POST -F "file=@large_file.jpg" \
     "https://yourdomain.com/api/v1/attachments?class=Portfolio"

# Monitor image processing performance
time curl "https://yourdomain.com/image_secure.php?model=Portfolio&size=large_thumb&filename=1.hash.jpg"

# Monitor API response times
curl -w "@curl-format.txt" -o /dev/null -s \
     "https://yourdomain.com/api/v1/users"
```

## Security Monitoring

### Log Analysis

```bash
# Monitor security events
tail -f logs/security.log | jq '.'

# Check for suspicious activity
grep "suspicious_activity" logs/security.log | jq '.details'

# Monitor failed login attempts
grep "login_failed" logs/security.log | jq '.ip' | sort | uniq -c | sort -nr

# Check rate limiting triggers
grep "rate_limit_exceeded" logs/security.log | jq '.ip' | sort | uniq -c
```

### Automated Security Scanning

```bash
# Run security audit
npm audit

# Check for known vulnerabilities
composer audit

# Scan for malware in uploads
clamscan -r media/

# Check SSL configuration
sslscan yourdomain.com

# Test security headers
curl -I https://yourdomain.com | grep -E "(X-|Strict-|Content-Security)"
```

## Rollback Procedures

### Emergency Rollback

```bash
# 1. Restore original files
cp server/php/image.php.backup server/php/image.php
cp server/php/download.php.backup server/php/download.php
cp server/php/Slim/public/index.php.backup server/php/Slim/public/index.php

# 2. Restore database (if needed)
pg_restore -h localhost -U username -d database_name backup_YYYYMMDD.sql

# 3. Clear caches
rm -rf cache/images/*
php artisan cache:clear  # if using Laravel
```

### Gradual Rollback

```bash
# 1. Disable new features via feature flags
echo "ENABLE_SECURE_UPLOADS=false" >> .env
echo "ENABLE_NEW_IMAGE_PROCESSOR=false" >> .env

# 2. Route traffic back to old handlers
# Update .htaccess or nginx config

# 3. Monitor for issues
tail -f logs/application.log
```

## Maintenance Tasks

### Daily

- Monitor security logs
- Check error rates
- Verify backup completion
- Review performance metrics

### Weekly

- Update security signatures
- Review access logs
- Check disk usage
- Update dependencies

### Monthly

- Security audit
- Performance review
- Backup testing
- Dependency updates

## Support and Troubleshooting

### Common Issues

1. **File Upload Failures**
   - Check file permissions
   - Verify upload directory exists
   - Check file size limits
   - Review security logs

2. **Image Processing Errors**
   - Check ImageMagick/GD installation
   - Verify cache directory permissions
   - Monitor memory usage
   - Check file format support

3. **Performance Issues**
   - Review database indexes
   - Check cache hit rates
   - Monitor server resources
   - Analyze slow queries

### Emergency Contacts

- Security Team: security@yourdomain.com
- DevOps Team: devops@yourdomain.com
- Database Admin: dba@yourdomain.com

## Conclusion

These security fixes and modernization updates significantly improve the platform's security posture and performance. Regular monitoring and maintenance are essential to ensure continued security and optimal performance.

For questions or issues during deployment, please contact the development team or refer to the troubleshooting section above.