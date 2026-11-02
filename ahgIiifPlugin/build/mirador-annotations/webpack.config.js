const path = require('path');
const webpack = require('webpack');

module.exports = {
  entry: './src/index.js',
  output: {
    path: path.resolve(__dirname, 'dist'),
    filename: 'mirador-annotations.min.js',
    library: { name: 'Mirador', type: 'umd', export: 'default' },
    globalObject: 'window',
  },
  // Mirador imports Node's url module; webpack 5 ships no polyfills.
  resolve: { fallback: { url: require.resolve('url/') } },
  performance: { hints: false },
  // One file: Mirador loads its translations with dynamic imports, which would
  // otherwise become separate chunks fetched from a guessed public path.
  plugins: [new webpack.optimize.LimitChunkCountPlugin({ maxChunks: 1 })],
};
