<?php
/**
 * Organization Verification Model
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

class OrganizationVerification extends AppModel
{
    protected $table = 'organization_verifications';
    
    protected $fillable = [
        'organization_id',
        'verification_type',
        'status',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
        'notes',
        'rejection_reason',
        'expiry_date',
        'verification_data'
    ];

    protected $hidden = [
        'reviewed_by',
        'verification_data'
    ];

    protected $casts = [
        'verification_data' => 'array',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'expiry_date' => 'date'
    ];

    protected $dates = [
        'submitted_at',
        'reviewed_at',
        'expiry_date'
    ];

    // Verification types
    const TYPE_BUSINESS_LICENSE = 'business_license';
    const TYPE_VAT_REGISTRATION = 'vat_registration';
    const TYPE_COMMERCIAL_REGISTRATION = 'commercial_registration';
    const TYPE_AUTHORIZED_SIGNATORY = 'authorized_signatory';
    const TYPE_BANK_VERIFICATION = 'bank_verification';
    const TYPE_ADDRESS_VERIFICATION = 'address_verification';
    const TYPE_IDENTITY_VERIFICATION = 'identity_verification';

    // Verification statuses
    const STATUS_PENDING = 'pending';
    const STATUS_IN_REVIEW = 'in_review';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_EXPIRED = 'expired';
    const STATUS_REQUIRES_UPDATE = 'requires_update';

    // Validation rules
    public static $rules = [
        'organization_id' => 'required|integer|exists:organizations,id',
        'verification_type' => 'required|in:business_license,vat_registration,commercial_registration,authorized_signatory,bank_verification,address_verification,identity_verification',
        'status' => 'required|in:pending,in_review,approved,rejected,expired,requires_update',
        'notes' => 'string|max:1000',
        'rejection_reason' => 'string|max:500',
        'expiry_date' => 'date|after:today',
        'verification_data' => 'array'
    ];

    /**
     * Relationships
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeInReview($query)
    {
        return $query->where('status', self::STATUS_IN_REVIEW);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', self::STATUS_EXPIRED)
                    ->orWhere(function($q) {
                        $q->where('expiry_date', '<', date('Y-m-d'))
                          ->whereIn('status', [self::STATUS_APPROVED, self::STATUS_IN_REVIEW]);
                    });
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_APPROVED)
                    ->where(function($q) {
                        $q->whereNull('expiry_date')
                          ->orWhere('expiry_date', '>=', date('Y-m-d'));
                    });
    }

    public function scopeByType($query, $type)
    {
        return $query->where('verification_type', $type);
    }

    public function scopeRequiringReview($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_IN_REVIEW]);
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
     * Check if verification is active/valid
     */
    public function isActive()
    {
        if ($this->status !== self::STATUS_APPROVED) {
            return false;
        }

        if ($this->expiry_date && $this->expiry_date < date('Y-m-d')) {
            return false;
        }

        return true;
    }

    /**
     * Check if verification is expired
     */
    public function isExpired()
    {
        return $this->expiry_date && $this->expiry_date < date('Y-m-d');
    }

    /**
     * Get days until expiry
     */
    public function getDaysUntilExpiry()
    {
        if (!$this->expiry_date) {
            return null;
        }

        $now = new \DateTime();
        $expiry = new \DateTime($this->expiry_date);
        $diff = $now->diff($expiry);

        return $diff->invert ? -$diff->days : $diff->days;
    }

    /**
     * Submit for review
     */
    public function submitForReview()
    {
        $this->status = self::STATUS_PENDING;
        $this->submitted_at = date('Y-m-d H:i:s');
        
        return $this->save();
    }

    /**
     * Start review process
     */
    public function startReview($reviewerId = null)
    {
        global $authUser;
        
        $this->status = self::STATUS_IN_REVIEW;
        $this->reviewed_by = $reviewerId ?: ($authUser ? $authUser->id : null);
        
        return $this->save();
    }

    /**
     * Approve verification
     */
    public function approve($notes = null, $expiryDate = null, $reviewerId = null)
    {
        global $authUser;
        
        $this->status = self::STATUS_APPROVED;
        $this->reviewed_at = date('Y-m-d H:i:s');
        $this->reviewed_by = $reviewerId ?: ($authUser ? $authUser->id : null);
        
        if ($notes) {
            $this->notes = $notes;
        }
        
        if ($expiryDate) {
            $this->expiry_date = $expiryDate;
        }
        
        $this->rejection_reason = null;
        
        return $this->save();
    }

    /**
     * Reject verification
     */
    public function reject($reason, $notes = null, $reviewerId = null)
    {
        global $authUser;
        
        $this->status = self::STATUS_REJECTED;
        $this->reviewed_at = date('Y-m-d H:i:s');
        $this->reviewed_by = $reviewerId ?: ($authUser ? $authUser->id : null);
        $this->rejection_reason = $reason;
        
        if ($notes) {
            $this->notes = $notes;
        }
        
        return $this->save();
    }

    /**
     * Mark as requiring update
     */
    public function requireUpdate($reason, $notes = null, $reviewerId = null)
    {
        global $authUser;
        
        $this->status = self::STATUS_REQUIRES_UPDATE;
        $this->reviewed_at = date('Y-m-d H:i:s');
        $this->reviewed_by = $reviewerId ?: ($authUser ? $authUser->id : null);
        $this->rejection_reason = $reason;
        
        if ($notes) {
            $this->notes = $notes;
        }
        
        return $this->save();
    }

    /**
     * Mark as expired
     */
    public function markExpired()
    {
        $this->status = self::STATUS_EXPIRED;
        return $this->save();
    }

    /**
     * Get verification requirements for type
     */
    public static function getRequirementsForType($type)
    {
        $requirements = [
            self::TYPE_BUSINESS_LICENSE => [
                'documents' => ['business_license_certificate'],
                'data_fields' => ['license_number', 'issue_date', 'expiry_date', 'issuing_authority'],
                'description' => 'Valid business license from relevant authority'
            ],
            self::TYPE_VAT_REGISTRATION => [
                'documents' => ['vat_certificate'],
                'data_fields' => ['vat_number', 'registration_date', 'tax_office'],
                'description' => 'VAT registration certificate with valid 15-digit VAT number'
            ],
            self::TYPE_COMMERCIAL_REGISTRATION => [
                'documents' => ['commercial_registration'],
                'data_fields' => ['cr_number', 'registration_date', 'expiry_date', 'chamber_of_commerce'],
                'description' => 'Commercial registration certificate from Chamber of Commerce'
            ],
            self::TYPE_AUTHORIZED_SIGNATORY => [
                'documents' => ['signatory_authorization', 'signatory_id'],
                'data_fields' => ['signatory_name', 'signatory_id_number', 'authorization_date'],
                'description' => 'Authorized signatory documentation and identification'
            ],
            self::TYPE_BANK_VERIFICATION => [
                'documents' => ['bank_letter', 'bank_statement'],
                'data_fields' => ['bank_name', 'account_number', 'iban', 'verification_date'],
                'description' => 'Bank verification letter and recent statement'
            ],
            self::TYPE_ADDRESS_VERIFICATION => [
                'documents' => ['address_proof'],
                'data_fields' => ['address', 'city', 'postal_code', 'verification_date'],
                'description' => 'Proof of business address (utility bill, lease agreement, etc.)'
            ],
            self::TYPE_IDENTITY_VERIFICATION => [
                'documents' => ['owner_id', 'owner_photo'],
                'data_fields' => ['owner_name', 'id_number', 'nationality', 'verification_date'],
                'description' => 'Business owner identity verification documents'
            ]
        ];

        return $requirements[$type] ?? [];
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
        
        if (!empty($params['type'])) {
            $query->byType($params['type']);
        }
        
        if (!empty($params['status'])) {
            $query->where('status', $params['status']);
        }
        
        if (!empty($params['submitted_from'])) {
            $query->where('submitted_at', '>=', $params['submitted_from']);
        }
        
        if (!empty($params['submitted_to'])) {
            $query->where('submitted_at', '<=', $params['submitted_to']);
        }
        
        if (!empty($params['expiring_within_days'])) {
            $date = date('Y-m-d', strtotime('+' . $params['expiring_within_days'] . ' days'));
            $query->where('expiry_date', '<=', $date)
                  ->where('expiry_date', '>=', date('Y-m-d'))
                  ->where('status', self::STATUS_APPROVED);
        }
        
        return $query;
    }

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($verification) {
            if (empty($verification->submitted_at)) {
                $verification->submitted_at = date('Y-m-d H:i:s');
            }
        });
        
        static::updated(function ($verification) {
            // Update organization verification status
            if ($verification->status === self::STATUS_APPROVED) {
                $organization = $verification->organization;
                
                // Check if all required verifications are approved
                $requiredTypes = [
                    self::TYPE_BUSINESS_LICENSE,
                    self::TYPE_VAT_REGISTRATION,
                    self::TYPE_COMMERCIAL_REGISTRATION
                ];
                
                $approvedTypes = $organization->verifications()
                    ->active()
                    ->pluck('verification_type')
                    ->toArray();
                
                if (count(array_intersect($requiredTypes, $approvedTypes)) === count($requiredTypes)) {
                    $organization->is_verified = true;
                    $organization->verified_at = date('Y-m-d H:i:s');
                    $organization->save();
                }
            }
        });
        
        // Auto-expire verifications
        static::saving(function ($verification) {
            if ($verification->isExpired() && $verification->status === self::STATUS_APPROVED) {
                $verification->status = self::STATUS_EXPIRED;
            }
        });
    }
}