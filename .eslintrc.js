module.exports = {
  "extends": [
    "plugin:nextcloud/recommended"
  ],
  "env": {
    "nextcloud/nextcloud": true,
  },
  "rules": {
    "nextcloud/no-deprecations": "warn",
    "nextcloud/no-removed-apis": "error",
  },
  "parserOptions": {
    "ecmaVersion": 7,
    "sourceType": "module",
    "ecmaFeatures": {
      "jsx": true,
    }
  }
}
