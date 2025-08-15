<div class="rfq-detail" ng-controller="RfqCtrl">
    <div class="container-fluid">
        <!-- Loading State -->
        <div ng-if="loading" class="text-center">
            <i class="fa fa-spinner fa-spin fa-2x"></i>
            <p>Loading RFQ details...</p>
        </div>
        
        <!-- RFQ Content -->
        <div ng-if="!loading && currentRfq">
            <div class="row">
                <!-- Main Content -->
                <div class="col-md-8">
                    <!-- RFQ Header -->
                    <div class="panel panel-default">
                        <div class="panel-body">
                            <div class="rfq-header">
                                <h2>{{currentRfq.title}}</h2>
                                <div class="rfq-meta">
                                    <span class="label label-{{currentRfq.status === 'open' ? 'success' : 'default'}}">
                                        {{currentRfq.status | uppercase}}
                                    </span>
                                    <span class="label label-info">{{currentRfq.category.name}}</span>
                                    <span ng-if="currentRfq.subcategory" class="label label-info">
                                        {{currentRfq.subcategory.name}}
                                    </span>
                                </div>
                                
                                <div class="rfq-details mt-3">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>Budget:</strong> 
                                                {{formatCurrency(currentRfq.budget_min, currentRfq.currency)}} - 
                                                {{formatCurrency(currentRfq.budget_max, currentRfq.currency)}}
                                            </p>
                                            <p><strong>Location:</strong> 
                                                {{currentRfq.location.city}}, {{currentRfq.location.country}}
                                            </p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Posted by:</strong> 
                                                <a href="#/profile/{{currentRfq.buyer.id}}">
                                                    {{currentRfq.buyer.username}}
                                                </a>
                                            </p>
                                            <p><strong>Posted:</strong> {{timeAgo(currentRfq.created_at)}}</p>
                                            <p ng-if="currentRfq.closing_at"><strong>Closes:</strong> 
                                                {{timeAgo(currentRfq.closing_at)}}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Tags -->
                                <div ng-if="currentRfq.tags.length > 0" class="rfq-tags mt-2">
                                    <span ng-repeat="tag in currentRfq.tags" class="label label-default">
                                        {{tag}}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- RFQ Description -->
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h4>Project Description</h4>
                        </div>
                        <div class="panel-body">
                            <div ng-bind-html="currentRfq.description | nl2br"></div>
                            
                            <!-- Attachments -->
                            <div ng-if="currentRfq.attachments.length > 0" class="mt-3">
                                <h5>Attachments</h5>
                                <ul class="list-unstyled">
                                    <li ng-repeat="attachment in currentRfq.attachments">
                                        <a href="{{attachment.url}}" target="_blank">
                                            <i class="fa fa-file"></i> {{attachment.name}}
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Bid Form (Vendors Only) -->
                    <div ng-if="canBid()" class="panel panel-success">
                        <div class="panel-heading">
                            <h4>Place Your Bid</h4>
                        </div>
                        <div class="panel-body">
                            <form ng-submit="placeBid()">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Bid Amount ({{currentRfq.currency}})</label>
                                            <input type="number" ng-model="newBid.amount" 
                                                   class="form-control" required 
                                                   min="{{currentRfq.budget_min}}" 
                                                   max="{{currentRfq.budget_max}}">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Delivery Time (Days)</label>
                                            <input type="number" ng-model="newBid.delivery_days" 
                                                   class="form-control" required min="1">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label>Proposal Message</label>
                                    <textarea ng-model="newBid.message" class="form-control" 
                                              rows="4" required 
                                              placeholder="Describe your approach and why you're the best fit..."></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-success" ng-disabled="loading">
                                    <i class="fa fa-paper-plane"></i> Submit Bid
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Existing Bids -->
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h4>Bids ({{bids.length}})</h4>
                        </div>
                        <div class="panel-body">
                            <div ng-if="bids.length === 0" class="text-center text-muted">
                                <i class="fa fa-inbox fa-2x"></i>
                                <p>No bids yet. Be the first to bid!</p>
                            </div>
                            
                            <div ng-repeat="bid in bids" class="bid-item panel panel-default">
                                <div class="panel-body">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="bid-vendor">
                                                <img ng-src="{{bid.vendor.avatar || '/images/default-avatar.png'}}" 
                                                     class="avatar-sm" alt="{{bid.vendor.username}}">
                                                <strong>
                                                    <a href="#/profile/{{bid.vendor.id}}">
                                                        {{bid.vendor.username}}
                                                    </a>
                                                </strong>
                                                <span class="vendor-rating">
                                                    <i class="fa fa-star text-warning"></i>
                                                    {{bid.vendor.rating || 'N/A'}}
                                                    ({{bid.vendor.review_count || 0}} reviews)
                                                </span>
                                            </div>
                                            
                                            <p class="bid-message mt-2">{{bid.message}}</p>
                                            
                                            <small class="text-muted">
                                                <i class="fa fa-clock-o"></i> {{timeAgo(bid.created_at)}}
                                            </small>
                                        </div>
                                        
                                        <div class="col-md-4 text-right">
                                            <div class="bid-amount">
                                                <h4 class="text-success">
                                                    {{formatCurrency(bid.amount, currentRfq.currency)}}
                                                </h4>
                                                <p class="text-muted">
                                                    <i class="fa fa-calendar"></i> 
                                                    {{bid.delivery_days}} days delivery
                                                </p>
                                            </div>
                                            
                                            <div ng-if="canAwardBid(bid)" class="mt-2">
                                                <button ng-click="awardBid(bid)" 
                                                        class="btn btn-primary btn-sm">
                                                    Award Project
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sidebar -->
                <div class="col-md-4">
                    <!-- RFQ Stats -->
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h4>RFQ Statistics</h4>
                        </div>
                        <div class="panel-body">
                            <div class="stat-item">
                                <strong>{{bids.length}}</strong>
                                <span class="text-muted">Bids Received</span>
                            </div>
                            <div class="stat-item">
                                <strong>{{currentRfq.views || 0}}</strong>
                                <span class="text-muted">Views</span>
                            </div>
                            <div class="stat-item">
                                <strong>{{timeAgo(currentRfq.created_at)}}</strong>
                                <span class="text-muted">Posted</span>
                            </div>
                            <div ng-if="currentRfq.closing_at" class="stat-item">
                                <strong>{{timeAgo(currentRfq.closing_at)}}</strong>
                                <span class="text-muted">Closes</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Buyer Info -->
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h4>About the Buyer</h4>
                        </div>
                        <div class="panel-body">
                            <div class="buyer-profile text-center">
                                <img ng-src="{{currentRfq.buyer.avatar || '/images/default-avatar.png'}}" 
                                     class="avatar-lg" alt="{{currentRfq.buyer.username}}">
                                <h5>
                                    <a href="#/profile/{{currentRfq.buyer.id}}">
                                        {{currentRfq.buyer.username}}
                                    </a>
                                </h5>
                                <div class="buyer-rating">
                                    <i class="fa fa-star text-warning"></i>
                                    {{currentRfq.buyer.rating || 'N/A'}}
                                    ({{currentRfq.buyer.review_count || 0}} reviews)
                                </div>
                                
                                <div class="buyer-stats mt-3">
                                    <div class="row">
                                        <div class="col-xs-6">
                                            <strong>{{currentRfq.buyer.projects_posted || 0}}</strong>
                                            <br><small>Projects Posted</small>
                                        </div>
                                        <div class="col-xs-6">
                                            <strong>{{currentRfq.buyer.total_spent || 0 | currency}}</strong>
                                            <br><small>Total Spent</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-3">
                                    <button ng-if="user.id !== currentRfq.buyer.id" 
                                            ng-click="contactBuyer()" 
                                            class="btn btn-primary btn-sm">
                                        <i class="fa fa-envelope"></i> Contact
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Related RFQs -->
                    <div ng-if="relatedRfqs.length > 0" class="panel panel-default">
                        <div class="panel-heading">
                            <h4>Related RFQs</h4>
                        </div>
                        <div class="panel-body">
                            <div ng-repeat="rfq in relatedRfqs | limitTo:5" class="related-rfq">
                                <h6>
                                    <a href="#/rfq/{{rfq.id}}/{{rfq.slug_title}}">
                                        {{rfq.title | limitTo:50}}
                                    </a>
                                </h6>
                                <small class="text-muted">
                                    {{formatCurrency(rfq.budget_min, rfq.currency)}} - 
                                    {{formatCurrency(rfq.budget_max, rfq.currency)}}
                                </small>
                                <hr ng-if="!$last">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Share RFQ -->
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h4>Share this RFQ</h4>
                        </div>
                        <div class="panel-body text-center">
                            <div class="social-share">
                                <a href="#" ng-click="shareOnFacebook()" class="btn btn-primary btn-sm">
                                    <i class="fa fa-facebook"></i> Facebook
                                </a>
                                <a href="#" ng-click="shareOnTwitter()" class="btn btn-info btn-sm">
                                    <i class="fa fa-twitter"></i> Twitter
                                </a>
                                <a href="#" ng-click="shareOnLinkedIn()" class="btn btn-primary btn-sm">
                                    <i class="fa fa-linkedin"></i> LinkedIn
                                </a>
                            </div>
                            
                            <div class="mt-2">
                                <input type="text" class="form-control" 
                                       value="{{getCurrentUrl()}}" readonly 
                                       ng-click="selectText($event)">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Error State -->
        <div ng-if="!loading && !currentRfq" class="text-center">
            <i class="fa fa-exclamation-triangle fa-3x text-warning"></i>
            <h4>RFQ Not Found</h4>
            <p class="text-muted">The requested RFQ could not be found or may have been removed.</p>
            <a href="#/rfq" class="btn btn-primary">Browse All RFQs</a>
        </div>
    </div>
</div> return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: currency || 'USD'
        }).format(amount);
    };

    $scope.timeAgo = function(date) {
        return moment(date).fromNow();
    };

    // Initialize controller
    $scope.init();

'use strict';

angular.module('getlancerApp')
  .controller('RfqCtrl', function ($scope, $http, $routeParams, $location, $window, flash, $filter) {
    
    // Initialize scope variables
    $scope.rfqs = [];
    $scope.currentRfq = null;
    $scope.relatedRfqs = [];
    $scope.bids = [];
    $scope.categories = [];
    $scope.loading = false;
    $scope.user = {}; // Current logged-in user
    
    // Filters for RFQ list
    $scope.filters = {
      search: '',
      category_id: '',
      budget_min: '',
      budget_max: '',
      location: '',
      status: ''
    };
    
    // Pagination
    $scope.pagination = {
      current_page: 1,
      per_page: 10,
      total: 0
    };
    
    // New bid form
    $scope.newBid = {
      amount: '',
      delivery_days: '',
      message: ''
    };
    
    // Initialize controller
    $scope.init = function() {
      $scope.loadCurrentUser();
      $scope.loadCategories();
      
      if ($routeParams.id) {
        $scope.loadRfqDetail($routeParams.id);
      } else {
        $scope.loadRfqs();
      }
    };
    
    // Load current user
    $scope.loadCurrentUser = function() {
      $http.get('/api/v1/me')
        .then(function(response) {
          $scope.user = response.data.data;
        })
        .catch(function(error) {
          console.error('Error loading user:', error);
        });
    };
    
    // Load categories
    $scope.loadCategories = function() {
      $http.get('/api/v1/categories')
        .then(function(response) {
          $scope.categories = response.data.data;
        })
        .catch(function(error) {
          console.error('Error loading categories:', error);
        });
    };
    
    // Load RFQ list
    $scope.loadRfqs = function(page) {
      $scope.loading = true;
      page = page || 1;
      
      var params = {
        page: page,
        per_page: $scope.pagination.per_page,
        search: $scope.filters.search,
        category_id: $scope.filters.category_id,
        budget_min: $scope.filters.budget_min,
        budget_max: $scope.filters.budget_max,
        location: $scope.filters.location,
        status: $scope.filters.status
      };
      
      // Remove empty parameters
      Object.keys(params).forEach(key => {
        if (params[key] === '' || params[key] === null || params[key] === undefined) {
          delete params[key];
        }
      });
      
      $http.get('/api/v1/rfqs', { params: params })
        .then(function(response) {
          $scope.rfqs = response.data.data;
          $scope.pagination = response.data.pagination;
          $scope.loading = false;
        })
        .catch(function(error) {
          console.error('Error loading RFQs:', error);
          flash.error = 'Failed to load RFQs. Please try again.';
          $scope.loading = false;
        });
    };
    
    // Load RFQ detail
    $scope.loadRfqDetail = function(id) {
      $scope.loading = true;
      
      $http.get('/api/v1/rfqs/' + id)
        .then(function(response) {
          $scope.currentRfq = response.data.data;
          $scope.loadRfqBids(id);
          $scope.loadRelatedRfqs();
          $scope.loading = false;
        })
        .catch(function(error) {
          console.error('Error loading RFQ detail:', error);
          flash.error = 'Failed to load RFQ details. Please try again.';
          $scope.loading = false;
        });
    };
    
    // Load RFQ bids
    $scope.loadRfqBids = function(rfqId) {
      $http.get('/api/v1/rfqs/' + rfqId + '/bids')
        .then(function(response) {
          $scope.bids = response.data.data;
        })
        .catch(function(error) {
          console.error('Error loading bids:', error);
        });
    };
    
    // Load related RFQs
    $scope.loadRelatedRfqs = function() {
      if (!$scope.currentRfq) return;
      
      var params = {
        category_id: $scope.currentRfq.category_id,
        exclude_id: $scope.currentRfq.id,
        limit: 5
      };
      
      $http.get('/api/v1/rfqs/related', { params: params })
        .then(function(response) {
          $scope.relatedRfqs = response.data.data;
        })
        .catch(function(error) {
          console.error('Error loading related RFQs:', error);
        });
    };
    
    // Apply filters
    $scope.applyFilters = function() {
      $scope.pagination.current_page = 1;
      $scope.loadRfqs();
    };
    
    // Clear filters
    $scope.clearFilters = function() {
      $scope.filters = {
        search: '',
        category_id: '',
        budget_min: '',
        budget_max: '',
        location: '',
        status: ''
      };
      $scope.applyFilters();
    };
    
    // Check if user can bid
    $scope.canBid = function() {
      if (!$scope.currentRfq || !$scope.user.id) return false;
      
      // Can't bid on own RFQ
      if ($scope.currentRfq.buyer_id === $scope.user.id) return false;
      
      // Can't bid if RFQ is closed
      if ($scope.currentRfq.status !== 'open') return false;
      
      // Can't bid if already bidded
      var existingBid = $scope.bids.find(function(bid) {
        return bid.vendor_id === $scope.user.id;
      });
      
      return !existingBid;
    };
    
    // Place bid
    $scope.placeBid = function() {
      if (!$scope.newBid.amount || !$scope.newBid.delivery_days || !$scope.newBid.message) {
        flash.error = 'Please fill in all required fields.';
        return;
      }
      
      if ($scope.newBid.amount < $scope.currentRfq.budget_min || 
          $scope.newBid.amount > $scope.currentRfq.budget_max) {
        flash.error = 'Bid amount must be within the specified budget range.';
        return;
      }
      
      $scope.loading = true;
      
      var bidData = {
        rfq_id: $scope.currentRfq.id,
        amount: $scope.newBid.amount,
        delivery_days: $scope.newBid.delivery_days,
        message: $scope.newBid.message
      };
      
      $http.post('/api/v1/rfq-bids', bidData)
        .then(function(response) {
          flash.success = 'Your bid has been submitted successfully!';
          $scope.newBid = { amount: '', delivery_days: '', message: '' };
          $scope.loadRfqBids($scope.currentRfq.id);
          $scope.loading = false;
        })
        .catch(function(error) {
          console.error('Error placing bid:', error);
          flash.error = error.data.message || 'Failed to submit bid. Please try again.';
          $scope.loading = false;
        });
    };
    
    // Check if user can award bid
    $scope.canAwardBid = function(bid) {
      if (!$scope.currentRfq || !$scope.user.id) return false;
      
      // Only RFQ owner can award
      if ($scope.currentRfq.buyer_id !== $scope.user.id) return false;
      
      // RFQ must be open
      if ($scope.currentRfq.status !== 'open') return false;
      
      return true;
    };
    
    // Award bid
    $scope.awardBid = function(bid) {
      if (!confirm('Are you sure you want to award this project to ' + bid.vendor.username + '?')) {
        return;
      }
      
      $scope.loading = true;
      
      $http.post('/api/v1/rfqs/' + $scope.currentRfq.id + '/award', {
        bid_id: bid.id
      })
        .then(function(response) {
          flash.success = 'Project has been awarded successfully!';
          $scope.loadRfqDetail($scope.currentRfq.id);
          $scope.loading = false;
        })
        .catch(function(error) {
          console.error('Error awarding bid:', error);
          flash.error = error.data.message || 'Failed to award project. Please try again.';
          $scope.loading = false;
        });
    };
    
    // Contact buyer
    $scope.contactBuyer = function() {
      $location.path('/messages/compose/' + $scope.currentRfq.buyer.id);
    };
    
    // Social sharing functions
    $scope.shareOnFacebook = function() {
      var url = encodeURIComponent($scope.getCurrentUrl());
      var title = encodeURIComponent($scope.currentRfq.title);
      var shareUrl = 'https://www.facebook.com/sharer/sharer.php?u=' + url + '&t=' + title;
      $window.open(shareUrl, '_blank', 'width=600,height=400');
    };
    
    $scope.shareOnTwitter = function() {
      var url = encodeURIComponent($scope.getCurrentUrl());
      var text = encodeURIComponent('Check out this RFQ: ' + $scope.currentRfq.title);
      var shareUrl = 'https://twitter.com/intent/tweet?url=' + url + '&text=' + text;
      $window.open(shareUrl, '_blank', 'width=600,height=400');
    };
    
    $scope.shareOnLinkedIn = function() {
      var url = encodeURIComponent($scope.getCurrentUrl());
      var title = encodeURIComponent($scope.currentRfq.title);
      var shareUrl = 'https://www.linkedin.com/sharing/share-offsite/?url=' + url + '&title=' + title;
      $window.open(shareUrl, '_blank', 'width=600,height=400');
    };
    
    // Get current URL
    $scope.getCurrentUrl = function() {
      return $window.location.href;
    };
    
    // Select text in input
    $scope.selectText = function(event) {
      event.target.select();
    };
    
    // Utility functions
    $scope.timeAgo = function(date) {
      return moment(date).fromNow();
    };
    
    $scope.formatCurrency = function(amount, currency) {
      return $filter('currency')(amount, currency || '$');
    };
    
    // Pagination functions
    $scope.goToPage = function(page) {
      if (page >= 1 && page <= $scope.pagination.total_pages) {
        $scope.pagination.current_page = page;
        $scope.loadRfqs(page);
      }
    };
    
    $scope.nextPage = function() {
      if ($scope.pagination.current_page < $scope.pagination.total_pages) {
        $scope.goToPage($scope.pagination.current_page + 1);
      }
    };
    
    $scope.prevPage = function() {
      if ($scope.pagination.current_page > 1) {
        $scope.goToPage($scope.pagination.current_page - 1);
      }
    };
    
    // Initialize controller
    $scope.init();
  });
