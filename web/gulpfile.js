var gulp            = require('gulp');
var concat          = require('gulp-concat');
var uglify          = require('gulp-uglify');
var sass            = require('gulp-sass');
var runSequence     = require('run-sequence').use(gulp);
var autoprefixer    =  require('gulp-autoprefixer');
var flatten         = require('gulp-flatten');
var replace         = require('gulp-replace');

var manifest = require('asset-builder')('manifest.json');

var js = manifest.getDependencyByName('habanero.js');
var css = manifest.getDependencyByName('habanero.css');
var globs = manifest.globs;
var path = manifest.paths;

gulp.task('js', function(cb) {
    return gulp.src(js.globs)
        .pipe(concat(js.name))
        .pipe(uglify(), {
            compress: {
                'drop_debugger': true
            }
        })
        .pipe(gulp.dest(path.dist));
});

gulp.task('css', function(cb) {
    return gulp.src(css.globs)
        .pipe(sass({
            style: 'compressed',
            includePaths: ['.']
        }))
        .pipe(replace('../fonts/', 'fonts/'))
        .pipe(replace('fonts/bootstrap/', 'fonts/'))
        .pipe(autoprefixer(), {
            browsers: [
                'last 2 versions',
                'android 4',
                'opera 12'
            ]
        })
        .pipe(gulp.dest(path.dist));
});

gulp.task('fonts', function() {
    return gulp.src(globs.fonts)
        .pipe(flatten())
        .pipe(gulp.dest(path.dist + 'fonts'))
});


gulp.task('default', function(callback) {
    runSequence('js', 'css', 'fonts');
});