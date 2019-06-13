module.exports = function(grunt) {
	grunt.initConfig({
		cacheBust: {
			bustjsfiles: {
				options: {
					assets: ['js/**/*.js'],
					queryString: true
				},
				src: ['index.html', 'cpmidx.html']
			}
		}
	});
	grunt.loadNpmTasks('grunt-cache-bust');
	grunt.registerTask('default', ["cacheBust"]);
};