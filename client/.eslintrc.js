module.exports = {
    'env': {
        'browser': true,
        'es6': true,
        'node': true,
        'jquery': true
    },
    'extends': [
        'eslint:recommended'
    ],
    'globals': {
        'angular': 'readonly',
        'moment': 'readonly',
        'console': 'readonly'
    },
    'parserOptions': {
        'ecmaVersion': 2018,
        'sourceType': 'module'
    },
    'rules': {
        'indent': [
            'error',
            4
        ],
        'linebreak-style': [
            'error',
            'unix'
        ],
        'quotes': [
            'error',
            'single'
        ],
        'semi': [
            'error',
            'always'
        ],
        'no-unused-vars': [
            'warn'
        ],
        'no-console': [
            'warn'
        ],
        'no-debugger': [
            'error'
        ]
    }
};