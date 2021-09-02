module.exports = {
  mode: 'production',
  entry: {
    'eidlogin-adminsettings': './src/eidlogin-adminsettings.js',
    'eidlogin-personalsettings': './src/eidlogin-personalsettings.js',
  },
  output: {
    clean: true,
    filename: '[name].js',
    path: __dirname + '/js',
  },
  module: {
    rules: [
      {
        test: /\.s[ac]ss$/i,
        use: [
          // Creates `style` nodes from JS strings
          "style-loader",
          // Translates CSS into CommonJS
          "css-loader",
          // Compiles Sass to CSS
          "sass-loader",
        ]
      },
      {
        test: /\.svg$/,
        use: {
          loader: 'svg-url-loader'
        }
      },
    ],
  },
};