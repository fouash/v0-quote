<?php
/**
 * Organization Setting Model
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

class OrganizationSetting extends AppModel
{
    protected $table = 'organization_settings';
    
    protected $fillable = [
        'organization_id',
        'setting_key',
        'setting_value',
        'setting_type',
        'is_encrypted',
        'is_public',
        'description',
        'updated_by'
    ];

    protected $hidden = [
        'updated_by'
    ];

    protected $casts = [
        'is_encrypted' => 'boolean',
        'is_public' => 'boolean'
    ];

    // Setting types
    const TYPE_STRING = 'string';
    const TYPE_INTEGER = 'integer';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_JSON = 'json';
    const TYPE_ARRAY = 'array';
    const TYPE_FLOAT = 'float';
    const TYPE_DATE = 'date';
    const TYPE_DATETIME = 'datetime';

    // Common setting keys
    const KEY_TIMEZONE = 'timezone';
    const KEY_CURRENCY = 'currency';
    const KEY_LANGUAGE = 'language';
    const KEY_DATE_FORMAT = 'date_format';
    const KEY_TIME_FORMAT = 'time_format';
    const KEY_NOTIFICATION_EMAIL = 'notification_email';
    const KEY_BILLING_EMAIL = 'billing_email';
    const KEY_SUPPORT_EMAIL = 'support_email';
    const KEY_PHONE = 'phone';
    const KEY_FAX = 'fax';
    const KEY_WEBSITE = 'website';
    const KEY_LOGO_URL = 'logo_url';
    const KEY_THEME_COLOR = 'theme_color';
    const KEY_AUTO_APPROVE_USERS = 'auto_approve_users';
    const KEY_REQUIRE_APPROVAL = 'require_approval';
    const KEY_MAX_USERS = 'max_users';
    const KEY_MAX_PROJECTS = 'max_projects';
    const KEY_MAX_STORAGE_MB = 'max_storage_mb';
    const KEY_BACKUP_FREQUENCY = 'backup_frequency';
    const KEY_SECURITY_LEVEL = 'security_level';
    const KEY_TWO_FACTOR_REQUIRED = 'two_factor_required';
    const KEY_SESSION_TIMEOUT = 'session_timeout';
    const KEY_PASSWORD_POLICY = 'password_policy';

    // Validation rules
    public static $rules = [
        'organization_id' => 'required|integer|exists:organizations,id',
        'setting_key' => 'required|string|max:100',
        'setting_value' => 'required',
        'setting_type' => 'required|in:string,integer,boolean,json,array,float,date,datetime',
        'description' => 'string|max:500'
    ];

    /**
     * Relationships
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scopes
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopePrivate($query)
    {
        return $query->where('is_public', false);
    }

    public function scopeEncrypted($query)
    {
        return $query->where('is_encrypted', true);
    }

    public function scopeByKey($query, $key)
    {
        return $query->where('setting_key', $key);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('setting_type', $type);
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
     * Get typed value based on setting_type
     */
    public function getTypedValue()
    {
        $value = $this->is_encrypted ? $this->decrypt($this->setting_value) : $this->setting_value;
        
        switch ($this->setting_type) {
            case self::TYPE_INTEGER:
                return (int) $value;
            case self::TYPE_BOOLEAN:
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case self::TYPE_FLOAT:
                return (float) $value;
            case self::TYPE_JSON:
            case self::TYPE_ARRAY:
                return json_decode($value, true);
            case self::TYPE_DATE:
                return date('Y-m-d', strtotime($value));
            case self::TYPE_DATETIME:
                return date('Y-m-d H:i:s', strtotime($value));
            default:
                return $value;
        }
    }

    /**
     * Set typed value based on setting_type
     */
    public function setTypedValue($value)
    {
        switch ($this->setting_type) {
            case self::TYPE_JSON:
            case self::TYPE_ARRAY:
                $this->setting_value = json_encode($value);
                break;
            case self::TYPE_BOOLEAN:
                $this->setting_value = $value ? '1' : '0';
                break;
            default:
                $this->setting_value = (string) $value;
        }
        
        if ($this->is_encrypted) {
            $this->setting_value = $this->encrypt($this->setting_value);
        }
    }

    /**
     * Encrypt value
     */
    private function encrypt($value)
    {
        // Use Laravel's encryption or implement custom encryption
        if (function_exists('encrypt')) {
            return base64_encode($value);
        }
        
        // Fallback to base64 encoding (not secure, implement proper encryption)
        return base64_encode($value);
    }

    /**
     * Decrypt value
     */
    private function decrypt($value)
    {
        // Use Laravel's decryption or implement custom decryption
        if (function_exists('decrypt')) {
            try {
                return base64_decode($value);
            } catch (\Exception $e) {
                return $value; // Return original if decryption fails
            }
        }
        
        // Fallback to base64 decoding
        return base64_decode($value);
    }

    /**
     * Get default settings for new organization
     */
    public static function getDefaultSettings()
    {
        return [
            [
                'setting_key' => self::KEY_TIMEZONE,
                'setting_value' => 'Asia/Riyadh',
                'setting_type' => self::TYPE_STRING,
                'is_public' => true,
                'description' => 'Organization timezone'
            ],
            [
                'setting_key' => self::KEY_CURRENCY,
                'setting_value' => 'SAR',
                'setting_type' => self::TYPE_STRING,
                'is_public' => true,
                'description' => 'Default currency'
            ],
            [
                'setting_key' => self::KEY_LANGUAGE,
                'setting_value' => 'en',
                'setting_type' => self::TYPE_STRING,
                'is_public' => true,
                'description' => 'Default language'
            ],
            [
                'setting_key' => self::KEY_DATE_FORMAT,
                'setting_value' => 'Y-m-d',
                'setting_type' => self::TYPE_STRING,
                'is_public' => true,
                'description' => 'Date format'
            ],
            [
                'setting_key' => self::KEY_TIME_FORMAT,
                'setting_value' => 'H:i:s',
                'setting_type' => self::TYPE_STRING,
                'is_public' => true,
                'description' => 'Time format'
            ],
            [
                'setting_key' => self::KEY_AUTO_APPROVE_USERS,
                'setting_value' => '0',
                'setting_type' => self::TYPE_BOOLEAN,
                'is_public' => false,
                'description' => 'Auto approve new users'
            ],
            [
                'setting_key' => self::KEY_REQUIRE_APPROVAL,
                'setting_value' => '1',
                'setting_type' => self::TYPE_BOOLEAN,
                'is_public' => false,
                'description' => 'Require approval for new users'
            ],
            [
                'setting_key' => self::KEY_MAX_USERS,
                'setting_value' => '50',
                'setting_type' => self::TYPE_INTEGER,
                'is_public' => false,
                'description' => 'Maximum number of users'
            ],
            [
                'setting_key' => self::KEY_MAX_PROJECTS,
                'setting_value' => '100',
                'setting_type' => self::TYPE_INTEGER,
                'is_public' => false,
                'description' => 'Maximum number of projects'
            ],
            [
                'setting_key' => self::KEY_MAX_STORAGE_MB,
                'setting_value' => '5120',
                'setting_type' => self::TYPE_INTEGER,
                'is_public' => false,
                'description' => 'Maximum storage in MB'
            ],
            [
                'setting_key' => self::KEY_SECURITY_LEVEL,
                'setting_value' => 'medium',
                'setting_type' => self::TYPE_STRING,
                'is_public' => false,
                'description' => 'Security level (low, medium, high)'
            ],
            [
                'setting_key' => self::KEY_TWO_FACTOR_REQUIRED,
                'setting_value' => '0',
                'setting_type' => self::TYPE_BOOLEAN,
                'is_public' => false,
                'description' => 'Require two-factor authentication'
            ],
            [
                'setting_key' => self::KEY_SESSION_TIMEOUT,
                'setting_value' => '3600',
                'setting_type' => self::TYPE_INTEGER,
                'is_public' => false,
                'description' => 'Session timeout in seconds'
            ]
        ];
    }

    /**
     * Get setting value by key for organization
     */
    public static function getValue($organizationId, $key, $default = null)
    {
        $setting = self::where('organization_id', $organizationId)
                      ->where('setting_key', $key)
                      ->first();
        
        return $setting ? $setting->getTypedValue() : $default;
    }

    /**
     * Set setting value by key for organization
     */
    public static function setValue($organizationId, $key, $value, $type = self::TYPE_STRING, $isEncrypted = false, $isPublic = true, $description = null)
    {
        global $authUser;
        
        $setting = self::where('organization_id', $organizationId)
                      ->where('setting_key', $key)
                      ->first();
        
        if (!$setting) {
            $setting = new self([
                'organization_id' => $organizationId,
                'setting_key' => $key,
                'setting_type' => $type,
                'is_encrypted' => $isEncrypted,
                'is_public' => $isPublic,
                'description' => $description
            ]);
        }
        
        $setting->setting_type = $type;
        $setting->is_encrypted = $isEncrypted;
        $setting->setTypedValue($value);
        $setting->updated_by = $authUser ? $authUser->id : null;
        
        return $setting->save();
    }

    /**
     * Get all settings for organization as key-value array
     */
    public static function getAllForOrganization($organizationId, $publicOnly = false)
    {
        $query = self::where('organization_id', $organizationId);
        
        if ($publicOnly) {
            $query->public();
        }
        
        $settings = $query->get();
        $result = [];
        
        foreach ($settings as $setting) {
            $result[$setting->setting_key] = $setting->getTypedValue();
        }
        
        return $result;
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
        
        if (!empty($params['key'])) {
            $query->byKey($params['key']);
        }
        
        if (!empty($params['type'])) {
            $query->byType($params['type']);
        }
        
        if (isset($params['public'])) {
            $query->where('is_public', $params['public']);
        }
        
        if (isset($params['encrypted'])) {
            $query->where('is_encrypted', $params['encrypted']);
        }
        
        if (!empty($params['search'])) {
            $query->where(function($q) use ($params) {
                $q->where('setting_key', 'LIKE', '%' . $params['search'] . '%')
                  ->orWhere('description', 'LIKE', '%' . $params['search'] . '%');
            });
        }
        
        return $query;
    }

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($setting) {
            global $authUser;
            if (isset($authUser) && $authUser) {
                $setting->updated_by = $authUser->id;
            }
        });
        
        static::updating(function ($setting) {
            global $authUser;
            if (isset($authUser) && $authUser) {
                $setting->updated_by = $authUser->id;
            }
        });
    }
}