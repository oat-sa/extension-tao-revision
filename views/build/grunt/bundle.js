module.exports = function(grunt) { 

    var requirejs   = grunt.config('requirejs') || {};
    var clean       = grunt.config('clean') || {};
    var copy        = grunt.config('copy') || {};

    var root        = grunt.option('root');
    var libs        = grunt.option('mainlibs');
    var ext         = require(root + '/tao/views/build/tasks/helpers/extensions')(grunt, root);

    /**
     * Remove bundled and bundling files
     */
    clean.taorevisionbundle = ['output',  root + '/taoDacSimple/views/js/controllers.min.js'];
    
    /**
     * Compile tao files into a bundle 
     */
    requirejs.taorevisionbundle = {
        options: {
            baseUrl : '../js',
            dir : 'output',
            mainConfigFile : './config/requirejs.build.js',
            paths : { 'taoDacSimple' : root + '/taoRevision/views/js' },
            modules : [{
                name: 'taoRevision/controller/routes',
                include : ext.getExtensionsControllers(['taoRevision']),
                exclude : ['mathJax', 'mediaElement'].concat(libs)
            }]
        }
    };

    /**
     * copy the bundles to the right place
     */
    copy.taorevisionbundle = {
        files: [
            { src: ['output/taoRevision/controller/routes.js'],  dest: root + '/taoRevision/views/js/controllers.min.js' },
            { src: ['output/taoRevision/controller/routes.js.map'],  dest: root + '/taoRevision/views/js/controllers.min.js.map' }
        ]
    };

    grunt.config('clean', clean);
    grunt.config('requirejs', requirejs);
    grunt.config('copy', copy);

    // bundle task
    grunt.registerTask('taorevisionbundle', ['clean:taorevisionbundle', 'requirejs:taorevisionbundle', 'copy:taorevisionbundle']);
};
