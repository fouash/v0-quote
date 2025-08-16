<?php
/**
 * Organization Attachment Model
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

class OrganizationAttachment extends AppModel
{
    protected $table = 'organization_attachments';
    
    protected $fillable = [
        'organization_id',
        'attachment_type',
        'file_name',
        'file_path',
        'original_name',
        'mime_type',
        'file_size_kb',
        'file_hash',
        'is_verified',
        'verified_at',
        'verified_by',
        'uploaded_by'
    ];

    protected $hidden = [
        'file_path',
        'uploaded_by',
        'verified_by'
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'uploaded_at' => 'datetime',
        'verified_at' => 'datetime'
    ];

    protected $dates = [
        'uploaded_at',
        'verified_at'
    ];

    // Validation rules
    public static $rules = [
        'organization_id' => 'required|integer|exists:organizations,id',
        'attachment_type' => 'required|in:VAT,CR,AVL,NWC,SE,MODON,Other',
        'file_name' => 'required|string|max:255',
        'file_path' => 'required|string|max:500',
        'original_name' => 'required|string|max:255',
        'mime_type' => 'required|in:application/pdf,image/jpeg,image/png,image/gif,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'file_size_kb' => 'required|integer|max:10240'
    ];

    /**
     * Relationships
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Scopes
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopePending($query)
    {
        return $query->where('is_verified', false);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('attachment_type', $type);
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
     * Get file URL for download
     */
    public function getDownloadUrl()
    {
        return '/download_secure.php?model=OrganizationAttachment&id=' . $this->organization_id . '&filename=' . md5('OrganizationAttachment' . $this->organization_id . 'docdownload') . '.' . pathinfo($this->file_name, PATHINFO_EXTENSION) . '/' . $this->id;
    }

    /**
     * Check if file exists on disk
     */
    public function fileExists()
    {
        return file_exists($this->file_path);
    }

    /**
     * Get file size in human readable format
     */
    public function getHumanFileSize()
    {
        $size = $this->file_size_kb * 1024;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, 2) . ' ' . $units[$i];
    }

    /**
     * Verify attachment
     */
    public function verify($verifiedBy = null)
    {
        global $authUser;
        
        $this->is_verified = true;
        $this->verified_at = date('Y-m-d H:i:s');
        $this->verified_by = $verifiedBy ?: ($authUser ? $authUser->id : null);
        
        return $this->save();
    }

    /**
     * Reject verification
     */
    public function reject()
    {
        $this->is_verified = false;
        $this->verified_at = null;
        $this->verified_by = null;
        
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
        
        if (!empty($params['type'])) {
            $query->byType($params['type']);
        }
        
        if (isset($params['verified'])) {
            $query->where('is_verified', $params['verified']);
        }
        
        if (!empty($params['uploaded_from'])) {
            $query->where('uploaded_at', '>=', $params['uploaded_from']);
        }
        
        if (!empty($params['uploaded_to'])) {
            $query->where('uploaded_at', '<=', $params['uploaded_to']);
        }
        
        return $query;
    }

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($attachment) {
            global $authUser;
            if (isset($authUser) && $authUser) {
                $attachment->uploaded_by = $authUser->id;
            }
            
            // Generate file hash if not provided
            if (empty($attachment->file_hash) && !empty($attachment->file_path) && file_exists($attachment->file_path)) {
                $attachment->file_hash = hash_file('sha256', $attachment->file_path);
            }
        });
        
        static::updated(function ($attachment) {
            // Update organization verification status if all required documents are verified
            if ($attachment->is_verified) {
                $organization = $attachment->organization;
                $requiredTypes = ['VAT', 'CR'];
                $verifiedTypes = $organization->attachments()->verified()->pluck('attachment_type')->toArray();
                
                if (count(array_intersect($requiredTypes, $verifiedTypes)) === count($requiredTypes)) {
                    $organization->is_verified = true;
                    $organization->save();
                }
            }
        });
        
        static::deleting(function ($attachment) {
            // Delete physical file
            if (file_exists($attachment->file_path)) {
                unlink($attachment->file_path);
            }
        });
    }
}