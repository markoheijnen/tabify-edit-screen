/* jshint node:true */
module.exports = function(grunt) {
	var path = require('path'),
		SOURCE_DIR = './',
		BUILD_DIR = 'build/';

	grunt.initConfig({
		clean: {
			all: [BUILD_DIR],
			dynamic: {
				dot: true,
				expand: true,
				cwd: BUILD_DIR,
				src: []
			}
		},
		copy: {
			files: {
				files: [
					{
						//dot: true,
						expand: true,
						cwd: SOURCE_DIR,
						src: [
							'**',
							'!**/.{svn,git}/**', // Ignore version control directories.
							'!**/output/**',

							'!**/bin/**',
							'!**/Gruntfile.js',
							'!**/node_modules/**',
							'!**/package.json',
							'!**/phpunit.xml',
							'!**/tests/**'
						],
						dest: BUILD_DIR
					}
				]
			}
		},
		glotpress_download: {
			core: {
				options: {
					domainPath: 'languages',
					url: 'http://wp-translate.org',
					slug: 'tabify-edit-screen',
					textdomain: 'tabify-edit-screen',
					filter: {
						minimum_percentage: 70
					}
				}
			}
		},
	});


	// Load plugins
	grunt.loadNpmTasks('grunt-contrib-clean');
	grunt.loadNpmTasks('grunt-contrib-copy');
	grunt.loadNpmTasks('grunt-glotpress');


	// Pre-commit task.
	grunt.registerTask('precommit', 'Runs front-end dev/test tasks in preparation for a commit.',
		['glotpress_download:core']);

	// Build task.
	grunt.registerTask('build', ['clean:all', 'glotpress_download:core', 'copy:files']);

	// Default task.
	grunt.registerTask('default', ['build']);
}