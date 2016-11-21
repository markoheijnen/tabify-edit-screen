/* jshint node:true */
module.exports = function(grunt) {
	var SOURCE_DIR = './',
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
							'!output/**',

							'!bin/**',
							'!build/**',
							'!Gruntfile.js',
							'!node_modules/**',
							'!phpunit.xml',
							'!tests/**'
						],
						dest: BUILD_DIR
					}
				]
			}
		},
		cssmin: {
			core: {
				expand: true,
				cwd: SOURCE_DIR,
				dest: BUILD_DIR,
				ext: '.min.css',
				src: [
					'css/*.css',
				]
			},
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
		makepot: {
			core: {
				options: {
					domainPath: '/languages',
					type: 'wp-plugin',
				}
			}
		},
		uglify: {
			core: {
				files: [{
					expand: true,
					cwd: 'js',
					src: '**/*.js',
					dest: BUILD_DIR + '/js',
					ext: '.min.js'
				}]
			}
		}
	});


	// Load plugins
	grunt.loadNpmTasks('grunt-contrib-clean');
	grunt.loadNpmTasks('grunt-contrib-copy');
	grunt.loadNpmTasks('grunt-contrib-cssmin');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-glotpress');
	grunt.loadNpmTasks('grunt-wp-i18n');


	// Pre-commit task.
	grunt.registerTask('precommit', 'Runs front-end dev/test tasks in preparation for a commit.',
		['glotpress_download:core', 'makepot:core', 'cssmin:core', 'uglify:core']);

	// Build task.
	grunt.registerTask('build', ['clean:all', 'precommit', 'copy:files']);

	// Default task.
	grunt.registerTask('default', ['build']);
}