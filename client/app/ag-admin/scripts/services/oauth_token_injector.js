'use strict';

angular.module('base')
    .factory('oauthTokenInjector', ['$cookies',
    function($cookies) {
        // Define URL mappings for cleaner code
        var urlMappings = {
            'contest_flags': 'flags',
            'portfolio_flags': 'flags',
            'job_flags': 'flags',
            'service_flags': 'flags',
            'project_flags': 'flags',
            'portfolio_followers': 'followers',
            'contest_followers': 'followers',
            'project_followers': 'followers',
            'service_flag_categories': 'flag_categories',
            'project_flag_categories': 'flag_categories',
            'contest_flag_categories': 'flag_categories',
            'job_flag_categories': 'flag_categories',
            'portfolio_flag_categories': 'flag_categories',
            'service_reviews': 'reviews',
            'entry_reviews': 'reviews',
            'project_reviews': 'reviews',
            'project_views': 'views',
            'service_views': 'views',
            'contest_views': 'views',
            'contest_user_views': 'views',
            'exam_views': 'views',
            'portfolio_views': 'views',
            'job_views': 'views',
            'user_flag_categories': 'flag_categories',
            'user_flags': 'flags',
            'user_followers': 'followers',
            'entry_flag_categories': 'flag_categories',
            'user_views': 'views'
        };

        function applyUrlMappings(url) {
            Object.keys(urlMappings).forEach(function(key) {
                if (url.indexOf(key) !== -1) {
                    url = url.replace(key, urlMappings[key]);
                }
            });
            return url;
        }

        var oauthTokenInjector = {
            request: function(config) {
                if (!config || !config.url) {
                    return config;
                }

                // Skip HTML files
                if (config.url.indexOf('.html') !== -1) {
                    return config;
                }

                // Add OAuth token
                var token = $cookies.get("token");
                if (token) {
                    var sep = config.url.indexOf('?') === -1 ? '?' : '&';
                    config.url = config.url + sep + 'token=' + encodeURIComponent(token);
                }

                // Apply URL mappings
                config.url = applyUrlMappings(config.url);

                return config;
            }
        };
        return oauthTokenInjector;
}]);