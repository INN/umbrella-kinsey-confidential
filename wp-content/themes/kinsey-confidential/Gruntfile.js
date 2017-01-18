module.exports = function(grunt) {
    'use strict';

    // Force use of Unix newlines
    grunt.util.linefeed = '\n';

    // Find what the current theme's directory is, relative to the WordPress root
    var path = process.cwd().replace(/^[\s\S]+\/wp-content/, "\/wp-content");

    var CSS_LESS_FILES = {
        'css/style.css': 'less/style.less',
        'homepages/assets/css/homepage.css': 'homepages/assets/less/homepage.less'
    };

    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

        less: {
            development: {
                options: {
                    paths: ['less', '../largo/less/inc'],
                    sourceMap: true,
                    outputSourceFiles: true,
                    sourceMapBasepath: path
                },
                files: CSS_LESS_FILES
            },
        },

        watch: {
            less: {
                files: [
                    'less/**/*.less',
                    'homepages/assets/less/**/*.less'
                ],
                tasks: [
                    'less:development',
                    'cssmin'
                ]
            },
        },

        cssmin: {
            target: {
                options: {
                    report: 'gzip'
                },
                files: [{
                    expand: true,
                    cwd: 'css',
                    src: ['*.css', '!*.min.css'],
                    dest: 'css',
                    ext: '.min.css'
                },
                {
                    expand: true,
                    cwd: 'homepages/assets/css',
                    src: ['*.css', '!*.min.css'],
                    dest: 'homepages/assets/css',
                    ext: '.min.css'
                }]
            }
        }

    });

    require('load-grunt-tasks')(grunt, { scope: 'devDependencies' });
}
