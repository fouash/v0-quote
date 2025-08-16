<?php
/**
 * Organization User Model
 *
 * PHP version 7+
 *
 * @category   Model
 * @package    GetlancerV3
 * @subpackage Organization
 * @author     Getlancer Team
 * @license    http://www.agriya.com/ Agriya Infoway Licence
 */

namespace Models;

class OrganizationUser extends AppModel
{
    protected $table = 'organization_users';
    
    protected $fillable = [
        'organization_id',
        'user_id',
        'role',
        'permissions',
        'is_active',
        'invited_by',
        'invited_at',
        'joined_at',
        'approved_at',
        'approved_by'
    ];

    protected $hidden = [
        'invited_by',
        'approved_by'
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_active' => 'boolean',
        'invited_at' => 'datetime',
        'joined_at' => 'datetime',
        'approved_at' => 'datetime'
    ];

    protected $dates = [
        'invited_at',
        'joined_at',
        'approved_at'
    ];

    // Available roles
    const ROLE_OWNER = 'owner';
    const ROLE_ADMIN = 'admin';
    const ROLE_MANAGER = 'manager';
    const ROLE_MEMBER = 'member';
    const ROLE_VIEWER = 'viewer';

    // Available permissions
    const PERMISSION_MANAGE_USERS = 'manage_users';
    const PERMISSION_MANAGE_SETTINGS = 'manage_settings';
    const PERMISSION_MANAGE_DOCUMENTS = 'manage_documents';
    const PERMISSION_VIEW_REPORTS = 'view_reports';
    const PERMISSION_MANAGE_PROJECTS = 'manage_projects';
    const PERMISSION_MANAGE_BILLING = 'manage_billing';

    // Validation rules
    public static $rules = [
        'organization_id' => 'required|integer|exists:organizations,id',
        'user_id' => 'required|integer|exists:users,id',
        'role' => 'required|in:owner,admin,manager,member,viewer',
        'permissions' => 'array',
        'permissions.*' => 'in:manage_users,manage_settings,manage_documents,view_reports,manage_projects,manage_billing'
    ];

    /**
     * Relationships
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function invitedBy()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopePending($query)
    {
        return $query->whereNull('approved_at');
    }

    public function scopeApproved($query)
    {
        return $query->whereNotNull('approved_at');
    }

    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    public function scopeOwners($query)
    {
        return $query->byRole(self::ROLE_OWNER);
    }

    public function scopeAdmins($query)
    {
        return $query->byRole(self::ROLE_ADMIN);
    }

    /**
     * Custom validation method
     */
    public function validate($data, $rules = array(), $messages = array())
    {
        if (empty($rules)) {
            $rules = self::$rules;
        }
        return parent::validate($data, $rules, $messages);
    }

    /**
     * Check if user has specific permission
     */
    public function hasPermission($permission)
    {
        if (!$this->is_active) {
            return false;
        }

        // Owners have all permissions
        if ($this->role === self::ROLE_OWNER) {
            return true;
        }

        // Check if permission is in the permissions array
        return in_array($permission, $this->permissions ?: []);
    }

    /**
     * Check if user can manage other users
     */
    public function canManageUsers()
    {
        return $this->hasPermission(self::PERMISSION_MANAGE_USERS) || 
               in_array($this->role, [self::ROLE_OWNER, self::ROLE_ADMIN]);
    }

    /**
     * Check if user can manage organization settings
     */
    public function canManageSettings()
    {
        return $this->hasPermission(self::PERMISSION_MANAGE_SETTINGS) || 
               in_array($this->role, [self::ROLE_OWNER, self::ROLE_ADMIN]);
    }

    /**
     * Check if user can manage documents
     */
    public function canManageDocuments()
    {
        return $this->hasPermission(self::PERMISSION_MANAGE_DOCUMENTS) || 
               in_array($this->role, [self::ROLE_OWNER, self::ROLE_ADMIN, self::ROLE_MANAGER]);
    }

    /**
     * Get default permissions for role
     */
    public static function getDefaultPermissions($role)
    {
        $permissions = [
            self::ROLE_OWNER => [
                self::PERMISSION_MANAGE_USERS,
                self::PERMISSION_MANAGE_SETTINGS,
                self::PERMISSION_MANAGE_DOCUMENTS,
                self::PERMISSION_VIEW_REPORTS,
                self::PERMISSION_MANAGE_PROJECTS,
                self::PERMISSION_MANAGE_BILLING
            ],
            self::ROLE_ADMIN => [
                self::PERMISSION_MANAGE_USERS,
                self::PERMISSION_MANAGE_SETTINGS,
                self::PERMISSION_MANAGE_DOCUMENTS,
                self::PERMISSION_VIEW_REPORTS,
                self::PERMISSION_MANAGE_PROJECTS
            ],
            self::ROLE_MANAGER => [
                self::PERMISSION_MANAGE_DOCUMENTS,
                self::PERMISSION_VIEW_REPORTS,
                self::PERMISSION_MANAGE_PROJECTS
            ],
            self::ROLE_MEMBER => [
                self::PERMISSION_VIEW_REPORTS
            ],
            self::ROLE_VIEWER => []
        ];

        return $permissions[$role] ?? [];
    }

    /**
     * Approve user membership
     */
    public function approve($approvedBy = null)
    {
        global $authUser;
        
        $this->approved_at = date('Y-m-d H:i:s');
        $this->approved_by = $approvedBy ?: ($authUser ? $authUser->id : null);
        $this->is_active = true;
        
        if (empty($this->joined_at)) {
            $this->joined_at = date('Y-m-d H:i:s');
        }
        
        return $this->save();
    }

    /**
     * Deactivate user membership
     */
    public function deactivate()
    {
        $this->is_active = false;
        return $this->save();
    }

    /**
     * Activate user membership
     */
    public function activate()
    {
        $this->is_active = true;
        return $this->save();
    }

    /**
     * Update user role and permissions
     */
    public function updateRole($role, $customPermissions = null)
    {
        $this->role = $role;
        
        if ($customPermissions !== null) {
            $this->permissions = $customPermissions;
        } else {
            $this->permissions = self::getDefaultPermissions($role);
        }
        
        return $this->save();
    }

    /**
     * Filter method for API queries
     */
    public function scopeFilter($query, $params = array())
    {
        parent::scopeFilter($query, $params);
        
        if (!empty($params['organization_id'])) {
            $query->where('organization_id', $params['organization_id']);
        }
        
        if (!empty($params['user_id'])) {
            $query->where('user_id', $params['user_id']);
        }
        
        if (!empty($params['role'])) {
            $query->byRole($params['role']);
        }
        
        if (isset($params['pending'])) {
            if ($params['pending']) {
                $query->pending();
            } else {
                $query->approved();
            }
        }
        
        if (!empty($params['joined_from'])) {
            $query->where('joined_at', '>=', $params['joined_from']);
        }
        
        if (!empty($params['joined_to'])) {
            $query->where('joined_at', '<=', $params['joined_to']);
        }
        
        return $query;
    }

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($organizationUser) {
            global $authUser;
            
            if (isset($authUser) && $authUser) {
                $organizationUser->invited_by = $authUser->id;
            }
            
            $organizationUser->invited_at = date('Y-m-d H:i:s');
            
            // Set default permissions if not provided
            if (empty($organizationUser->permissions)) {
                $organizationUser->permissions = self::getDefaultPermissions($organizationUser->role);
            }
        });
        
        static::created(function ($organizationUser) {
            // Auto-approve if the user is the organization owner
            $organization = $organizationUser->organization;
            if ($organization && $organization->owner_id === $organizationUser->user_id) {
                $organizationUser->approve();
            }
        });
    }
}