module.exports = function( grunt ) {

	'use strict';
	var banner = '/**\n * <%= pkg.homepage %>\n * Copyright (c) <%= grunt.template.today("yyyy") %>\n * This file is generated automatically. Do not edit.\n */\n';
	// Project configuration
	grunt.initConfig( {

		pkg: grunt.file.readJSON( 'package.json' ),

		addtextdomain: {
			options: {
				textdomain: 'vandergraaf-page-generator',
			},
			target: {
				files: {
					src: [ '*.php', '**/*.php', '!node_modules/**', '!php-tests/**', '!bin/**' ]
				}
			}
		},

		wp_readme_to_markdown: {
			your_target: {
				files: {
					'README.md': 'readme.txt'
				}
			},
		},

		makepot: {
			target: {
				options: {
					domainPath: '/languages',
					mainFile: 'vandergraaf-page-generator.php',
					potFilename: 'vandergraaf-page-generator.pot',
					potHeaders: {
						poedit: true,
						'x-poedit-keywordslist': true
					},
					type: 'wp-plugin',
					updateTimestamp: true
				}
			}
		},

		copy: {
			main: {
			  files: [
					// includes files within path and its sub-directories
					{
						expand: true, 
						cwd: './', 
						src: ['**', '!node_modules/**', '!php-tests/**', '!bin/**', '!tests/**'], 
						// dest: '/Users/mark/docker/wordpress/wp-app/wp-content/plugins/vandergraaf/',
						dest: "/Users/mark/Local\ Sites/productfocusphotography/app/public/wp-content/plugins/vandergraaf-s3-storage/"
					},
				],
			},
		},

		watch: {
			files: [ '**/*.php', '**/*.js' ],
			tasks: [ 'copy' ],
			livereload: {
			// Here we watch the files the sass task will compile to
			// These files are sent to the live reload server after sass compiles to them
			  options: { livereload: true },
			  files: ['/Users/mark/Local\ Sites/productfocusphotography/app/public/wp-content/plugins/vandergraaf-s3-storage/**/*'],
			},
		  },
	  
	} );

	grunt.loadNpmTasks( 'grunt-wp-i18n' );
	grunt.loadNpmTasks( 'grunt-wp-readme-to-markdown' );
	grunt.loadNpmTasks( 'grunt-contrib-copy' );
	grunt.loadNpmTasks( 'grunt-contrib-watch' );
	
	grunt.registerTask( 'i18n', ['addtextdomain', 'makepot'] );
	grunt.registerTask( 'readme', ['wp_readme_to_markdown'] );
	grunt.registerTask( 'build', ['copy', 'watch' ] );


	grunt.util.linefeed = '\n';

};
