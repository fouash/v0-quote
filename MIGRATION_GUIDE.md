# Migration Guide - Dependency Updates

## Quick Update Process

1. **Backup your project** before proceeding
2. Ensure you have **Node.js 18+** installed
3. Run the update script: `update-dependencies.bat`
4. Test your application

## Manual Update Steps

If you prefer manual updates:

```bash
# Update root dependencies
npm install

# Update client dependencies
cd client
npm install

# Update bower dependencies
bower install

# Run security audit
npm audit
```

## Potential Issues & Solutions

### jQuery 3.x Migration
- **Issue**: Some jQuery plugins may not work with jQuery 3.x
- **Solution**: Check plugin compatibility or use jQuery Migrate plugin

### AngularJS 1.8.3 Updates
- **Issue**: Minor API changes from 1.5.2
- **Solution**: Review AngularJS migration guide for 1.8.x

### Grunt Task Updates
- **Issue**: Some Grunt plugins may need configuration updates
- **Solution**: Check Grunt plugin documentation for latest configurations

### Node.js 18+ Requirement
- **Issue**: Older Node.js versions no longer supported
- **Solution**: Update to Node.js 18 LTS or newer

## Testing Checklist

- [ ] Application starts without errors
- [ ] Frontend loads correctly
- [ ] All JavaScript functionality works
- [ ] Forms and validation work
- [ ] File uploads function properly
- [ ] API endpoints respond correctly
- [ ] Admin panel accessible
- [ ] No console errors in browser

## Rollback Plan

If issues occur:
1. Restore from backup
2. Use `npm install` with previous package.json versions
3. Report issues in the project repository