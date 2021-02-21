const config = require('./config.js');
const { src, dest, series, parallel } = require('gulp');
const uglify = require("gulp-uglify");
const concat = require("gulp-concat");
const minify_css = require("gulp-minify-css");
const clean = require('gulp-clean');
const flatten = require('gulp-flatten');
const babel = require('gulp-babel');
const sass = require("gulp-sass");

function clean_style() {
    return src(config.paths.styles.dest + '*')
        .pipe(clean());
}

function style() {
    return src(config.paths.styles.src)
        .pipe(sass())
        .pipe(minify_css())
        .pipe(dest(config.paths.styles.dest))
}

function vendor_style() {
    return src(config.paths.vendor_styles)
        .pipe(flatten())
        .pipe(dest(config.paths.styles.dest))
}

function clean_script() {
    return src(config.paths.scripts.dest + '*')
        .pipe(clean());
}

function script() {
    return src(config.paths.scripts.src)
        .pipe(concat('apiopenstudio.min.js'))
        .pipe(babel())
        .pipe(uglify())
        .pipe(dest(config.paths.scripts.dest))
}

function vendor_script() {
    return src(config.paths.vendor_scripts)
        .pipe(flatten())
        .pipe(dest(config.paths.scripts.dest))
}

const css = series(clean_style, parallel(style, vendor_style));
const js = series(clean_script, parallel(script, vendor_script));
const build = parallel(css, js);

exports.style = css;
exports.script = js;
exports.default = build;
