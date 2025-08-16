<?php
/**
 * Organization Model
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

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\Rule;

class Organization extends AppModel
{
    use SoftDeletes;

    protected $table = 'organizations';
    
    protected $fillable = [
        'organization_name',
        'organization_type',
        'address_line1',
        'national_address',
        'city',
        'state_province',
        'country',
        'contact_email',
        'contact_phone',
        'vat_number',
        'cr_number',
        'avl_number',
        'nwc_number',
        'se_number',
        'modon_number',
        'is_active',
        'is_verified',
        'created_by',
        'updated_by'
    ];

    protected $hidden = [
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    // Validation rules
    public static $rules = [
        'organization_name' => 'required|string|max:255|unique:organizations,organization_name',
        'organization_type' => 'required|in:Buyer,Supplier,Both',
        'address_line1' => 'required|string|max:255',
        'national_address' => 'required|regex:/^[0-9]{8}$/',
        'city' => 'required|string|max:100',
        'state_province' => 'required|string|max:100',
        'country' => 'required|string|max:50',
        'contact_email' => 'required|email|max:255|unique:organizations,contact_email',
        'contact_phone' => 'required|regex:/^\+?[0-9\-\s\(\)]+$/|max:20',
        'vat_number' => 'required|regex:/^3[0-9]{14}$/|unique:organizations,vat_number',
        'cr_number' => 'required|regex:/^[0-9]{10}$/|unique:organizations,cr_number',
        'avl_number' => 'nullable|string|max:20|unique:organizations,avl_number',
        'nwc_number' => 'nullable|string|max:20|unique:organizations,nwc_number',
        'se_number' => 'nullable|string|max:20|unique:organizations,se_number',
        'modon_number' => 'nullable|string|max:20|unique:organizations,modon_number'
    ];

    // Update rules (without unique constraints for same record)
    public static function updateRules($id)
    {
        return [
            'organization_name' => 'required|string|max:255|unique:organizations,organization_name,' . $id,
            'organization_type' => 'required|in:Buyer,Supplier,Both',
            'address_line1' => 'required|string|max:255',
            'national_address' => 'required|regex:/^[0-9]{8}$/',
            'city' => 'required|string|max:100',
            'state_province' => 'required|string|max:100',
            'country' => 'required|string|max:50',
            'contact_email' => 'required|email|max:255|unique:organizations,contact_email,' . $id,
            'contact_phone' => 'required|regex:/^\+?[0-9\-\s\(\)]+$/|max:20',
            'vat_number' => 'required|regex:/^3[0-9]{14}$/|unique:organizations,vat_number,' . $id,
            'cr_number' => 'required|regex:/^[0-9]{10}$/|unique:organizations,cr_number,' . $id,
            'avl_number' => 'nullable|string|max:20|unique:organizations,avl_number,' . $id,
            'nwc_number' => 'nullable|string|max:20|unique:organizations,nwc_number,' . $id,
            'se_number' => 'nullable|string|max:20|unique:organizations,se_number,' . $id,
            'modon_number' => 'nullable|string|max:20|unique:organizations,modon_number,' . $id
        ];
    }

    /**
     * Relationships
     */
    public function attachments()
    {
        return $this->hasMany(OrganizationAttachment::class, 'organization_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'organization_users', 'organization_id', 'user_id')
                    ->withPivot('role', 'permissions', 'is_active', 'joined_at')
                    ->wherePivot('is_active', true);
    }

    public function organizationUsers()
    {
        return $this->hasMany(OrganizationUser::class, 'organization_id');
    }

    public function verifications()
    {
        return $this->hasMany(OrganizationVerification::class, 'organization_id');
    }

    public function settings()
    {
        return $this->hasMany(OrganizationSetting::class, 'organization_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('organization_type', $type);
    }

    public function scopeByLocation($query, $city = null, $state = null, $country = null)
    {
        if ($city) {
            $query->where('city', 'like', '%' . $city . '%');
        }
        if ($state) {
            $query->where('state_province', 'like', '%' . $state . '%');
        }
        if ($country) {
            $query->where('country', 'like', '%' . $country . '%');
        }
        return $query;
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('organization_name', 'like', '%' . $search . '%')
              ->orWhere('vat_number', 'like', '%' . $search . '%')
              ->orWhere('cr_number', 'like', '%' . $search . '%')
              ->orWhere('contact_email', 'like', '%' . $search . '%')
              ->orWhere('address_line1', 'like', '%' . $search . '%');
        });
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
    
    public function validateForUpdate($data, $id)
    {
        $rules = self::updateRules($id);
        return parent::validate($data, $rules);
    }

    /**
     * Check if organization can perform specific action
     */
    public function canPerformAction($action, $user = null)
    {
        switch ($action) {
            case 'create_quote':
                return $this->is_active && $this->is_verified && 
                       in_array($this->organization_type, ['Buyer', 'Both']);
            
            case 'submit_bid':
                return $this->is_active && $this->is_verified && 
                       in_array($this->organization_type, ['Supplier', 'Both']);
            
            case 'manage_users':
                if ($user) {
                    $orgUser = $this->organizationUsers()
                                   ->where('user_id', $user->id)
                                   ->where('is_active', true)
                                   ->first();
                    return $orgUser && in_array($orgUser->role, ['Admin', 'Manager']);
                }
                return false;
            
            default:
                return false;
        }
    }

    /**
     * Get organization statistics
     */
    public function getStats()
    {
        return [
            'total_users' => $this->users()->count(),
            'total_attachments' => $this->attachments()->count(),
            'verified_attachments' => $this->attachments()->where('is_verified', true)->count(),
            'pending_verifications' => $this->verifications()->where('status', 'Pending')->count(),
            'approved_verifications' => $this->verifications()->where('status', 'Approved')->count(),
            'last_activity' => $this->updated_at,
            'verification_status' => $this->getVerificationStatus()
        ];
    }

    /**
     * Get verification status details
     */
    public function getVerificationStatus()
    {
        $requiredDocs = ['VAT', 'CR'];
        $uploadedDocs = $this->attachments()->pluck('attachment_type')->toArray();
        $verifiedDocs = $this->attachments()->where('is_verified', true)->pluck('attachment_type')->toArray();
        
        return [
            'overall_verified' => $this->is_verified,
            'required_documents' => $requiredDocs,
            'uploaded_documents' => $uploadedDocs,
            'verified_documents' => $verifiedDocs,
            'missing_documents' => array_diff($requiredDocs, $uploadedDocs),
            'pending_verification' => array_diff($uploadedDocs, $verifiedDocs),
            'completion_percentage' => count($verifiedDocs) / count($requiredDocs) * 100
        ];
    }

    /**
     * Filter method for API queries
     */
    public function scopeFilter($query, $params = array())
    {
        // Call parent filter first
        parent::scopeFilter($query, $params);
        
        if (!empty($params['search'])) {
            $query->search($params['search']);
        }
        
        if (!empty($params['type'])) {
            $query->byType($params['type']);
        }
        
        if (!empty($params['city'])) {
            $query->byLocation($params['city']);
        }
        
        if (!empty($params['state'])) {
            $query->byLocation(null, $params['state']);
        }
        
        if (!empty($params['country'])) {
            $query->byLocation(null, null, $params['country']);
        }
        
        if (isset($params['verified'])) {
            $query->where('is_verified', $params['verified']);
        }
        
        if (!empty($params['created_from'])) {
            $query->where('created_at', '>=', $params['created_from']);
        }
        
        if (!empty($params['created_to'])) {
            $query->where('created_at', '<=', $params['created_to']);
        }
        
        return $query;
    }

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($organization) {
            // Set created_by if available
            global $authUser;
            if (isset($authUser) && $authUser) {
                $organization->created_by = $authUser->id;
            }
        });
        
        static::updating(function ($organization) {
            // Set updated_by if available
            global $authUser;
            if (isset($authUser) && $authUser) {
                $organization->updated_by = $authUser->id;
            }
        });
        
        static::created(function ($organization) {
            // Create default settings
            $defaultSettings = [
                'notification_email' => 'true',
                'notification_sms' => 'false',
                'auto_approve_quotes' => 'false',
                'max_quote_amount' => '1000000',
                'preferred_currency' => 'SAR',
                'business_hours' => '{"start": "08:00", "end": "17:00", "timezone": "Asia/Riyadh"}'
            ];
            
            foreach ($defaultSettings as $key => $value) {
                OrganizationSetting::create([
                    'organization_id' => $organization->id,
                    'setting_key' => $key,
                    'setting_value' => $value,
                    'setting_type' => is_numeric($value) ? 'number' : (in_array($value, ['true', 'false']) ? 'boolean' : (strpos($value, '{') === 0 ? 'json' : 'string')),
                    'is_public' => in_array($key, ['preferred_currency', 'business_hours'])
                ]);
            }
            
            // Add creator as admin if available
            global $authUser;
            if (isset($authUser) && $authUser) {
                OrganizationUser::create([
                    'organization_id' => $organization->id,
                    'user_id' => $authUser->id,
                    'role' => 'Admin',
                    'is_active' => true,
                    'approved_by' => $authUser->id,
                    'approved_at' => date('Y-m-d H:i:s')
                ]);
            }
        });
    }
}