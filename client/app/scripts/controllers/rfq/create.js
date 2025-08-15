
// ... existing controller code

$scope.createRfq = function() {
    if ($scope.rfqForm.$valid) {
        $scope.isSubmitting = true;
        
        // Process the RFQ data
        var rfqData = angular.copy($scope.rfq);
        
        // Convert skills text to array
        if (rfqData.skills_text) {
            rfqData.skills = rfqData.skills_text.split(',').map(function(skill) {
                return skill.trim();
            }).filter(function(skill) {
                return skill.length > 0;
            });
        }
        
        // Convert invited vendors to array
        if (rfqData.invited_vendors) {
            rfqData.invited_vendor_list = rfqData.invited_vendors.split(',').map(function(vendor) {
                return vendor.trim();
            }).filter(function(vendor) {
                return vendor.length > 0;
            });
        }
        
        // Submit the RFQ
        RfqService.create(rfqData).then(function(response) {
            $scope.isSubmitting = false;
            toastr.success('RFQ published successfully!');
            $location.path('/rfq/' + response.data.id);
        }).catch(function(error) {
            $scope.isSubmitting = false;
            toastr.error('Failed to publish RFQ. Please try again.');
            console.error('RFQ creation error:', error);
        });
    }
};

$scope.saveDraft = function() {
    var draftData = angular.copy($scope.rfq);
    draftData.status = 'draft';
    
    RfqService.saveDraft(draftData).then(function(response) {
        toastr.success('RFQ saved as draft');
        $location.path('/rfq/drafts');
    }).catch(function(error) {
        toastr.error('Failed to save draft');
        console.error('Draft save error:', error);
    });
};

$scope.previewRfq = function() {
    if ($scope.rfqForm.$valid) {
        $('#rfqPreviewModal').modal('show');
    }
};

$scope.publishFromPreview = function() {
    $('#rfqPreviewModal').modal('hide');
    $scope.createRfq();
};

$scope.getCategoryName = function(categoryId) {
    var category = $scope.categories.find(function(cat) {
        return cat.id == categoryId;
    });
    return category ? category.name : '';
};

$scope.formatFileSize = function(bytes) {
    if (bytes === 0) return '0 Bytes';
    var k = 1024;
    var sizes = ['Bytes', 'KB', 'MB', 'GB'];
    var i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

$scope.getMinDate = function() {
    var now = new Date();
    now.setHours(now.getHours() + 1); // Minimum 1 hour from now
    return now.toISOString().slice(0, 16);
};
