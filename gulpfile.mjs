import gulp from "gulp";

import * as dartSass from "sass";
import gulpSass from "gulp-sass";
const sass = gulpSass(dartSass);

import imagemin from "gulp-imagemin";
import imageminMozjpeg from "imagemin-mozjpeg";  // Importa o plugin mozjpeg
import imageminOptipng from "imagemin-optipng";  // Importa o plugin optipng
import imageminSvg from "imagemin-svgo";  // Importa o plugin optipng

import autoprefixer from "gulp-autoprefixer";
import concat from "gulp-concat";
import babel from "gulp-babel";
import uglify from "gulp-uglify";
import rename from "gulp-rename";
import gulpIf from 'gulp-if';
import sourcemaps from 'gulp-sourcemaps';

import browserSync from "browser-sync";
const browserSyncInstance = browserSync.create();

import path from 'path'; // Importa o módulo 'path' do Node.js

const projectName = "fozplaza";

// SERVER
// function startServer() {
//     browserSyncInstance.init({
//         proxy: "https://fozplaza.local",
//     });
// }
function startServer(done) {
    const certsPath = process.env.CUSTOM_localwpcerts;
    console.log('Certificados LocalWP:', certsPath);
    // C:\Users\Kaka Mari Davi\AppData\Roaming\Local\run\router\nginx\certs
    browserSyncInstance.init(
        {
            proxy: "https://fozplaza.semanabr25.inkweb.local",
            port: 3000,
            host: 'fozplaza.semanabr25.inkweb.local', // define o hostname para que o certificado seja válido
            https: {
                key: `${certsPath}/fozplaza.semanabr25.inkweb.local.key`,
                cert: `${certsPath}/fozplaza.semanabr25.inkweb.local.crt`,
            },
            // ui: { port: 3002 },
            ui: false,
            notify: false,
            open: false,
            logLevel: "info", // Para ver mais detalhes nos logs
        },
        () => done()
    );
}
// Exportação das tarefas do Gulp
export { startServer };

// OTIMIZA IMAGENS
function optimizeImg() {
    return gulp
        .src("dev/img/**/*.{jpg,png}")
        // .pipe(
        //     imagemin([
        //         imageminMozjpeg({ quality: 70, progressive: true }),
        //         imageminOptipng({ optimizationLevel: 1 }),
        //     ])
        // )
        .pipe(gulp.dest("dist/img"));
}

// OTIMIZA SVG
function optimizeSvg() {
    return gulp
        .src("dev/svg/*.svg")
        .pipe(
            imagemin(
                [
                    imageminSvg({
                        plugins: [
                            { name: 'removeViewBox', active: false },
                            // { name : 'cleanupIDs', active : false },
                        ],
                    }),
                ]
                // {
                //   verbose: true,
                // },
            )
        )
        .pipe(gulp.dest("dist/svg"));
}

// COMPILA JAVASCRIPT PRÓPRIOS
function compileJs() {
    return (
        gulp
            // .src("dev/js/app/*.js")
            .src(
                [
                    'dev/js/app/init.js', // 1. Primeiro arquivo - inicialização do Alpine
                    'dev/js/app/!(init)*.js', // 2. Todos os outros arquivos JS
                ],
                { allowEmpty: true }
            )
            .pipe(sourcemaps.init())
            // .pipe(rename({ suffix: ".min" }))
            .pipe(concat(`${projectName}-js.min.js`))
            // .pipe(filter(["*", "!js/scripts/teste.js"]))
            .pipe(babel({ presets: ["@babel/env"] }).on("error", console.error))
            .pipe(uglify().on("error", console.error))
            .pipe(gulp.dest("dist/js/app"))
            .pipe(browserSyncInstance.stream())
    );
}
// COMPILA JAVASCRIPTS LIBS
function compileLibJs() {
    return (
        gulp
            .src("dev/js/libs/*.js")
            .pipe(gulpIf(file => !file.basename.endsWith('.min.js'), babel({ presets: ["@babel/env"] }).on("error", console.error))) //Garante compatibilidade com navegadores antigos
            .pipe(gulpIf(file => !file.basename.endsWith('.min.js'), uglify().on("error", console.error)))
            .pipe(gulpIf(file => !file.basename.endsWith('.min.js'), rename({ suffix: ".min" })))
            // .pipe(rename({ suffix: ".min" }))
            .pipe(gulp.dest("dist/js/libs"))
            .pipe(browserSyncInstance.stream())
    );
}

// Compile Sass to CSS
function compileSass() {
    return (
        gulp
            .src([`dev/scss/${projectName}-css.scss`])
            // .src(['scss/**/*.scss']) //Compila todos os arquivos e todas as pastas dentro de /scss
            .pipe(sass({
                outputStyle: "compressed",
                silenceDeprecations: ['legacy-js-api'], // Silencia o aviso
            }).on("error", sass.logError))

            .pipe(
                autoprefixer({
                    overrideBrowserslist: ["last 2 versions"],
                    cascade: false,
                })
            )

            .pipe(rename(`${projectName}-css.min.css`))
            .pipe(gulp.dest("dist/css"))
            .pipe(browserSyncInstance.stream())
    );
}

// Compile CSS LIBS
function compileCssLibs() {
    return (
        gulp
            .src(["dev/css/libs/*.{css,scss}"])
            .pipe(gulpIf(file => !file.basename.endsWith('.min.css'), sass({
                outputStyle: "compressed",
                silenceDeprecations: ['legacy-js-api'], // Silencia o aviso
            }).on("error", sass.logError)))
            .pipe(gulpIf(file => !file.basename.endsWith('.min.css'), autoprefixer({
                overrideBrowserslist: ["last 2 versions"],
                cascade: false,
            })))
            .pipe(gulpIf(file => !file.basename.endsWith('.min.css'), rename({ suffix: ".min" })))
            .pipe(gulp.dest("dist/css/libs"))
            .pipe(browserSyncInstance.stream())
    );
}

// Compile third-party CSS libraries
function compileLibCSS() {
    return (gulp
        .src(["dev/scss/libs.scss"])
        .pipe(sass({
            outputStyle: "compressed",
            silenceDeprecations: ['legacy-js-api'], // Silencia o aviso
        }).on("error", sass.logError))

        .pipe(
            autoprefixer({
                overrideBrowserslist: ["last 2 versions"],
                cascade: false,
            })
        )

        .pipe(rename("libs.min.css"))
        .pipe(gulp.dest("dist/css"))
        .pipe(browserSyncInstance.stream())
    );
}

// Watch files for changes
function watch() {
    gulp.watch("dev/scss/**/*.scss", gulp.series(compileSass));
    gulp.watch("dev/scss/libs/*.scss", gulp.series(compileLibCSS));
    gulp.watch("dev/css/libs/*.{css,scss}", gulp.series(compileCssLibs));
    gulp.watch("dev/js/app/*.js", gulp.series(compileJs));
    gulp.watch("dev/js/libs/*.js", gulp.series(compileLibJs));
    gulp.watch("dev/img/**/*.{jpg,png}", gulp.series(optimizeImg));
    gulp.watch("dev/svg/*.svg", gulp.series(optimizeSvg));
    gulp.watch(['**/*.php', '**/**/*.php']).on('change', browserSyncInstance.reload);
}

// Define Gulp tasks
gulp.task("sass", compileSass);
gulp.task("libcss", compileLibCSS);
gulp.task("cssLibs", compileCssLibs);
gulp.task("js", compileJs);
gulp.task("libjs", compileLibJs);
gulp.task("img", optimizeImg);
gulp.task("svg", optimizeSvg);
gulp.task("server", startServer);
gulp.task("watch", watch);

// Default task to run all tasks in parallel
gulp.task(
    "default",
    gulp.parallel(
        "sass",
        "libcss",
        "cssLibs",
        "js",
        "libjs",
        "img",
        "svg",
        "server",
        "watch"
    )
);