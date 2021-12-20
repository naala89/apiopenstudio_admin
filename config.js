module.exports = {
    paths: {
        styles: {
            src: ["./includes/scss/*.scss"],
            dest: "./public/css/"
        },
        scripts: {
            src: ["./includes/js/*.js"],
            dest: "./public/js/"
        },
        vendor_scripts: [
            "./includes/vendor/**/*.js",
            "./includes/vendor/**/*.js.map"
        ],
        vendor_styles: [
            "./includes/vendor/**/*.css",
            "./includes/vendor/**/*.css.map"
        ]
    }
};
