<?php

namespace Models;

class Rfq extends AppModel
{
    protected $table = 'rfqs';
    protected $fillable = [
        'title', 'slug_title', 'description', 'buyer_id', 'category_id', 
        'subcategory_id', 'tags', 'budget_min', 'budget_max', 'currency',
        'location', 'status', 'is_public', 'closing_at', 'attachments'
    ];

    // Relationships
    public function buyer()
    {
        return $this->belongsTo('Models\User', 'buyer_id', 'id')
            ->where('role_id', 2); // Assuming role_id 2 is buyer
    }

    public function category()
    {
        return $this->belongsTo('Models\Category', 'category_id', 'id');
    }

    public function subcategory()
    {
        return $this->belongsTo('Models\Category', 'subcategory_id', 'id');
    }

    public function bids()
    {
        return $this->hasMany('Models\Bid', 'rfq_id', 'id')
            ->orderBy('created_at', 'desc');
    }

    public function awarded_bid()
    {
        return $this->hasOne('Models\Bid', 'rfq_id', 'id')
            ->where('awarded', true);
    }

    public function conversations()
    {
        return $this->hasMany('Models\Conversation', 'rfq_id', 'id');
    }

    // Scope for related RFQs algorithm
    public function scopeRelated($query, $rfqId, $categoryId, $tags = [])
    {
        return $query->where('id', '!=', $rfqId)
            ->where('is_public', true)
            ->where('status', 'open')
            ->where(function($q) use ($categoryId, $tags) {
                $q->where('category_id', $categoryId);
                if (!empty($tags)) {
                    $q->orWhereRaw("tags && ?", ['{' . implode(',', $tags) . '}']);
                }
            })
            ->orderByRaw('
                CASE WHEN category_id = ? THEN 3 ELSE 0 END +
                CASE WHEN tags && ? THEN 2 ELSE 0 END +
                CASE WHEN created_at > NOW() - INTERVAL '30 days' THEN 1 ELSE 0 END DESC
            ', [$categoryId, '{' . implode(',', $tags) . '}'])
            ->limit(10);
    }
}