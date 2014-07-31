/* jshint node:true */
module.exports = function(grunt) {
	var path = require('path'),
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
		glotpress_download: {
			core: {
				options: {
					domainPath: 'languages',
					url: 'http://wp-translate.org',
					slug: 'tabify-edit-screen',
					textdomain: 'tabify-edit-screen',
				}
			}
		},
	});

	// Load plugins
	grunt.loadNpmTasks('grunt-contrib-clean');
	grunt.loadNpmTasks('grunt-glotpress');

	// Build task.
	grunt.registerTask('build', ['clean:all', 'glotpress_download:core']);

	// Default task.
	grunt.registerTask('default', ['build']);
}