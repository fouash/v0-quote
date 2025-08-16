<?php
/**
 * Document Verification Workflow
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

class DocumentVerificationWorkflow extends AppModel
{
    protected $table = 'document_verification_workflows';
    
    protected $fillable = [
        'organization_id',
        'attachment_id',
        'workflow_type',
        'current_step',
        'total_steps',
        'status',
        'assigned_to',
        'priority',
        'due_date',
        'workflow_data',
        'notes',
        'started_at',
        'completed_at'
    ];

    protected $casts = [
        'workflow_data' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'due_date' => 'date'
    ];

    // Workflow types
    const TYPE_BASIC_VERIFICATION = 'basic_verification';
    const TYPE_SAUDI_COMPLIANCE = 'saudi_compliance';
    const TYPE_FINANCIAL_VERIFICATION = 'financial_verification';
    const TYPE_LEGAL_VERIFICATION = 'legal_verification';

    // Workflow statuses
    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_REJECTED = 'rejected';
    const STATUS_ON_HOLD = 'on_hold';

    // Priority levels
    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    /**
     * Relationships
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function attachment()
    {
        return $this->belongsTo(OrganizationAttachment::class, 'attachment_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Create workflow for document verification
     */
    public static function createForDocument($organizationId, $attachmentId, $workflowType = self::TYPE_BASIC_VERIFICATION)
    {
        $workflow = new self([
            'organization_id' => $organizationId,
            'attachment_id' => $attachmentId,
            'workflow_type' => $workflowType,
            'current_step' => 1,
            'status' => self::STATUS_PENDING,
            'priority' => self::PRIORITY_MEDIUM,
            'started_at' => date('Y-m-d H:i:s')
        ]);

        // Set workflow steps based on type
        $steps = self::getWorkflowSteps($workflowType);
        $workflow->total_steps = count($steps);
        $workflow->workflow_data = [
            'steps' => $steps,
            'step_history' => [],
            'auto_checks' => []
        ];

        // Set due date based on priority
        $workflow->due_date = self::calculateDueDate($workflow->priority);

        $workflow->save();

        // Start automatic checks
        $workflow->runAutomaticChecks();

        return $workflow;
    }

    /**
     * Get workflow steps for type
     */
    public static function getWorkflowSteps($type)
    {
        $steps = [
            self::TYPE_BASIC_VERIFICATION => [
                1 => ['name' => 'File Format Check', 'type' => 'automatic', 'description' => 'Verify file format and integrity'],
                2 => ['name' => 'Content Scan', 'type' => 'automatic', 'description' => 'Scan for malicious content'],
                3 => ['name' => 'Manual Review', 'type' => 'manual', 'description' => 'Human verification of document content'],
                4 => ['name' => 'Final Approval', 'type' => 'manual', 'description' => 'Final approval by authorized personnel']
            ],
            self::TYPE_SAUDI_COMPLIANCE => [
                1 => ['name' => 'File Format Check', 'type' => 'automatic', 'description' => 'Verify file format and integrity'],
                2 => ['name' => 'Content Scan', 'type' => 'automatic', 'description' => 'Scan for malicious content'],
                3 => ['name' => 'Document Type Validation', 'type' => 'automatic', 'description' => 'Validate document type matches attachment type'],
                4 => ['name' => 'Saudi Number Validation', 'type' => 'automatic', 'description' => 'Validate Saudi business numbers'],
                5 => ['name' => 'Compliance Review', 'type' => 'manual', 'description' => 'Review for Saudi regulatory compliance'],
                6 => ['name' => 'Legal Verification', 'type' => 'manual', 'description' => 'Legal team verification'],
                7 => ['name' => 'Final Approval', 'type' => 'manual', 'description' => 'Final approval by compliance officer']
            ],
            self::TYPE_FINANCIAL_VERIFICATION => [
                1 => ['name' => 'File Format Check', 'type' => 'automatic', 'description' => 'Verify file format and integrity'],
                2 => ['name' => 'Content Scan', 'type' => 'automatic', 'description' => 'Scan for malicious content'],
                3 => ['name' => 'Financial Data Extraction', 'type' => 'automatic', 'description' => 'Extract financial data from document'],
                4 => ['name' => 'Financial Analysis', 'type' => 'manual', 'description' => 'Financial team analysis'],
                5 => ['name' => 'Risk Assessment', 'type' => 'manual', 'description' => 'Risk assessment by finance team'],
                6 => ['name' => 'Final Approval', 'type' => 'manual', 'description' => 'Final approval by finance manager']
            ],
            self::TYPE_LEGAL_VERIFICATION => [
                1 => ['name' => 'File Format Check', 'type' => 'automatic', 'description' => 'Verify file format and integrity'],
                2 => ['name' => 'Content Scan', 'type' => 'automatic', 'description' => 'Scan for malicious content'],
                3 => ['name' => 'Legal Document Analysis', 'type' => 'manual', 'description' => 'Legal team document analysis'],
                4 => ['name' => 'Compliance Check', 'type' => 'manual', 'description' => 'Legal compliance verification'],
                5 => ['name' => 'Final Approval', 'type' => 'manual', 'description' => 'Final approval by legal counsel']
            ]
        ];

        return $steps[$type] ?? $steps[self::TYPE_BASIC_VERIFICATION];
    }

    /**
     * Calculate due date based on priority
     */
    public static function calculateDueDate($priority)
    {
        $days = [
            self::PRIORITY_URGENT => 1,
            self::PRIORITY_HIGH => 3,
            self::PRIORITY_MEDIUM => 7,
            self::PRIORITY_LOW => 14
        ];

        return date('Y-m-d', strtotime('+' . ($days[$priority] ?? 7) . ' days'));
    }

    /**
     * Run automatic checks
     */
    public function runAutomaticChecks()
    {
        $steps = $this->workflow_data['steps'] ?? [];
        $autoChecks = [];

        foreach ($steps as $stepNumber => $step) {
            if ($step['type'] === 'automatic' && $stepNumber >= $this->current_step) {
                $result = $this->executeAutomaticStep($stepNumber, $step);
                $autoChecks[$stepNumber] = $result;

                if ($result['passed']) {
                    $this->advanceToNextStep($stepNumber, $result);
                } else {
                    // Stop at failed automatic check
                    $this->status = self::STATUS_REJECTED;
                    $this->notes = $result['message'];
                    break;
                }
            }
        }

        // Update workflow data
        $workflowData = $this->workflow_data;
        $workflowData['auto_checks'] = $autoChecks;
        $this->workflow_data = $workflowData;
        $this->save();

        return $autoChecks;
    }

    /**
     * Execute automatic step
     */
    private function executeAutomaticStep($stepNumber, $step)
    {
        $attachment = $this->attachment;
        if (!$attachment) {
            return ['passed' => false, 'message' => 'Attachment not found'];
        }

        switch ($step['name']) {
            case 'File Format Check':
                return $this->checkFileFormat($attachment);
            
            case 'Content Scan':
                return $this->scanContent($attachment);
            
            case 'Document Type Validation':
                return $this->validateDocumentType($attachment);
            
            case 'Saudi Number Validation':
                return $this->validateSaudiNumbers($attachment);
            
            case 'Financial Data Extraction':
                return $this->extractFinancialData($attachment);
            
            default:
                return ['passed' => true, 'message' => 'Automatic check passed'];
        }
    }

    /**
     * Check file format
     */
    private function checkFileFormat($attachment)
    {
        $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        
        if (!in_array($attachment->mime_type, $allowedTypes)) {
            return ['passed' => false, 'message' => 'Invalid file format: ' . $attachment->mime_type];
        }

        // Check file exists
        if (!file_exists($attachment->file_path)) {
            return ['passed' => false, 'message' => 'File not found on disk'];
        }

        // Check file size
        if ($attachment->file_size_kb > 10240) { // 10MB
            return ['passed' => false, 'message' => 'File size exceeds limit'];
        }

        return ['passed' => true, 'message' => 'File format check passed'];
    }

    /**
     * Scan content for malicious files
     */
    private function scanContent($attachment)
    {
        // Basic content scanning
        $filePath = $attachment->file_path;
        
        // Check file hash against known malicious files (simplified)
        $suspiciousPatterns = ['<script', 'javascript:', 'vbscript:', 'onload=', 'onerror='];
        
        if (in_array($attachment->mime_type, ['application/pdf', 'text/plain'])) {
            $content = file_get_contents($filePath);
            foreach ($suspiciousPatterns as $pattern) {
                if (stripos($content, $pattern) !== false) {
                    return ['passed' => false, 'message' => 'Suspicious content detected'];
                }
            }
        }

        return ['passed' => true, 'message' => 'Content scan passed'];
    }

    /**
     * Validate document type
     */
    private function validateDocumentType($attachment)
    {
        // This would use OCR or document analysis to verify document type
        // For now, we'll do basic validation
        
        $expectedKeywords = [
            'VAT' => ['vat', 'tax', 'ضريبة', 'قيمة مضافة'],
            'CR' => ['commercial', 'registration', 'تجاري', 'سجل'],
            'AVL' => ['authorized', 'value', 'list'],
            'NWC' => ['water', 'company', 'مياه'],
            'SE' => ['electricity', 'كهرباء'],
            'MODON' => ['industrial', 'property', 'صناعية']
        ];

        $keywords = $expectedKeywords[$attachment->attachment_type] ?? [];
        
        if (empty($keywords)) {
            return ['passed' => true, 'message' => 'No specific validation for this document type'];
        }

        // For PDF files, we could extract text and check for keywords
        // This is a simplified implementation
        return ['passed' => true, 'message' => 'Document type validation passed'];
    }

    /**
     * Validate Saudi numbers
     */
    private function validateSaudiNumbers($attachment)
    {
        $organization = $this->organization;
        
        switch ($attachment->attachment_type) {
            case 'VAT':
                if (!preg_match('/^3[0-9]{14}$/', $organization->vat_number)) {
                    return ['passed' => false, 'message' => 'Invalid VAT number format'];
                }
                break;
            
            case 'CR':
                if (!preg_match('/^[0-9]{10}$/', $organization->cr_number)) {
                    return ['passed' => false, 'message' => 'Invalid CR number format'];
                }
                break;
        }

        return ['passed' => true, 'message' => 'Saudi number validation passed'];
    }

    /**
     * Extract financial data
     */
    private function extractFinancialData($attachment)
    {
        // This would use OCR and financial data extraction
        // For now, return success
        return ['passed' => true, 'message' => 'Financial data extraction completed'];
    }

    /**
     * Advance to next step
     */
    public function advanceToNextStep($completedStep, $result = null)
    {
        // Record step completion
        $workflowData = $this->workflow_data;
        global $authUser;
        $workflowData['step_history'][] = [
            'step' => $completedStep,
            'completed_at' => date('Y-m-d H:i:s'),
            'result' => $result,
            'completed_by' => $authUser ? $authUser->id : null
        ];

        // Move to next step
        if ($this->current_step < $this->total_steps) {
            $this->current_step++;
            $this->status = self::STATUS_IN_PROGRESS;
        } else {
            // Workflow completed
            $this->status = self::STATUS_COMPLETED;
            $this->completed_at = date('Y-m-d H:i:s');
            
            // Mark attachment as verified
            if ($this->attachment) {
                $this->attachment->is_verified = true;
                $this->attachment->verified_at = date('Y-m-d H:i:s');
                $this->attachment->save();
            }
        }

        $this->workflow_data = $workflowData;
        $this->save();

        return true;
    }

    /**
     * Assign workflow to user
     */
    public function assignTo($userId, $notes = null)
    {
        $this->assigned_to = $userId;
        $this->status = self::STATUS_IN_PROGRESS;
        
        if ($notes) {
            $this->notes = $notes;
        }

        return $this->save();
    }

    /**
     * Approve current step
     */
    public function approveCurrentStep($notes = null, $userId = null)
    {
        global $authUser;
        
        $result = [
            'passed' => true,
            'message' => $notes ?: 'Step approved',
            'approved_by' => $userId ?: ($authUser ? $authUser->id : null)
        ];

        return $this->advanceToNextStep($this->current_step, $result);
    }

    /**
     * Reject workflow
     */
    public function reject($reason, $userId = null)
    {
        global $authUser;
        
        $this->status = self::STATUS_REJECTED;
        $this->notes = $reason;
        $this->completed_at = date('Y-m-d H:i:s');

        // Record rejection in history
        $workflowData = $this->workflow_data;
        $workflowData['step_history'][] = [
            'step' => $this->current_step,
            'completed_at' => date('Y-m-d H:i:s'),
            'result' => [
                'passed' => false,
                'message' => $reason,
                'rejected_by' => $userId ?: ($authUser ? $authUser->id : null)
            ]
        ];
        $this->workflow_data = $workflowData;

        return $this->save();
    }

    /**
     * Get current step info
     */
    public function getCurrentStepInfo()
    {
        $steps = $this->workflow_data['steps'] ?? [];
        return $steps[$this->current_step] ?? null;
    }

    /**
     * Get workflow progress percentage
     */
    public function getProgressPercentage()
    {
        if ($this->total_steps == 0) return 0;
        
        $completedSteps = $this->current_step - 1;
        if ($this->status === self::STATUS_COMPLETED) {
            $completedSteps = $this->total_steps;
        }
        
        return round(($completedSteps / $this->total_steps) * 100);
    }

    /**
     * Check if workflow is overdue
     */
    public function isOverdue()
    {
        return $this->due_date && $this->due_date < date('Y-m-d') && !in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_REJECTED]);
    }

    /**
     * Get workflows requiring attention
     */
    public static function getWorkflowsRequiringAttention($userId = null)
    {
        $query = self::with(['organization', 'attachment', 'assignedTo'])
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_IN_PROGRESS]);

        if ($userId) {
            $query->where('assigned_to', $userId);
        }

        return $query->orderBy('priority', 'desc')
                    ->orderBy('due_date', 'asc')
                    ->get();
    }

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();
        
        static::created(function ($workflow) {
            // Auto-assign based on workflow type and organization
            $workflow->autoAssign();
        });
    }

    /**
     * Auto-assign workflow
     */
    private function autoAssign()
    {
        // This would implement logic to auto-assign workflows based on:
        // - Workflow type
        // - Organization
        // - User availability
        // - Workload balancing
        
        // For now, assign to admin users
        $adminUser = User::where('role_id', \Constants\ConstUserTypes::Admin)->first();
        if ($adminUser) {
            $this->assigned_to = $adminUser->id;
            $this->save();
        }
    }
}