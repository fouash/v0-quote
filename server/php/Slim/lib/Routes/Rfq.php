<?php

use Models\Rfq;
use Models\Bid;

// RFQ Routes
$app->group('/api/v1', function() use ($app) {
    
    // Browse RFQs
    $app->get('/rfq', function() use ($app) {
        $rfqs = Rfq::with(['buyer', 'category', 'bids'])
            ->where('is_public', true)
            ->where('status', 'open')
            ->paginate(20);
        
        return renderWithJson($rfqs);
    });

    // Get single RFQ with related RFQs
    $app->get('/rfq/:id/:title', function($id, $title) use ($app) {
        $rfq = Rfq::with(['buyer', 'category', 'bids.vendor', 'conversations'])
            ->find($id);
        
        if (!$rfq) {
            return renderWithJson(null, 'RFQ not found', '', 1, 404);
        }

        // Get related RFQs
        $related = Rfq::related($id, $rfq->category_id, $rfq->tags)->get();
        
        $response = [
            'rfq' => $rfq,
            'related_rfqs' => $related
        ];
        
        return renderWithJson($response);
    });

    // Create RFQ (buyers only)
    $app->post('/rfq', function() use ($app) {
        $user = getAuthenticatedUser();
        
        if (!$user || $user->role->name !== 'buyer') {
            return renderWithJson(null, 'Unauthorized', '', 1, 403);
        }

        $input = $app->request->post();
        $input['buyer_id'] = $user->id;
        $input['slug_title'] = slugify($input['title']);

        $rfq = Rfq::create($input);
        
        return renderWithJson($rfq, 'RFQ created successfully', '', 0, 201);
    });

    // Place bid (vendors only)
    $app->post('/rfq/:id/bids', function($id) use ($app) {
        $user = getAuthenticatedUser();
        
        if (!$user || $user->role->name !== 'vendor') {
            return renderWithJson(null, 'Unauthorized', '', 1, 403);
        }

        $rfq = Rfq::find($id);
        if (!$rfq || $rfq->status !== 'open') {
            return renderWithJson(null, 'RFQ not available for bidding', '', 1, 400);
        }

        $input = $app->request->post();
        $input['rfq_id'] = $id;
        $input['vendor_id'] = $user->id;

        $bid = Bid::create($input);
        
        // Trigger notification and WebSocket event
        NotificationService::createBidNotification($bid);
        
        return renderWithJson($bid, 'Bid placed successfully', '', 0, 201);
    });
});
