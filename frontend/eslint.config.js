// @ts-check
const eslint = require("@eslint/js");
const importPlugin = require("eslint-plugin-import");
const tseslint = require("typescript-eslint");
const angular = require("angular-eslint");

module.exports = tseslint.config(
  {
    files: ["**/*.ts"],
    extends: [
      eslint.configs.recommended,
      ...tseslint.configs.recommended,
      ...tseslint.configs.stylistic,
      ...angular.configs.tsRecommended,
      importPlugin.flatConfigs.recommended,
    ],
    processor: angular.processInlineTemplates,
    rules: {
      "eol-last": ["error", "always"],
      "import/first": ["error"],
      "import/newline-after-import": ["error"],
      "import/no-duplicates": ["error"],
      "import/no-self-import": ["error"],
      "import/no-unresolved": ["off"], // no-unresolved currently doesn't work properly with TypeScript
      "import/order": ["error", {
        "alphabetize": {
          "order": "asc",
          "caseInsensitive": true
        }
      }],
      "indent": ["error", 2],
      "no-trailing-spaces": ["error"],
      "quotes": ["error", "single"],
      "@angular-eslint/directive-selector": [
        "error",
        {
          type: "attribute",
          prefix: "app",
          style: "camelCase",
        },
      ],
      "@angular-eslint/component-selector": [
        "error",
        {
          type: "element",
          prefix: "app",
          style: "kebab-case",
        },
      ],
      "@typescript-eslint/no-explicit-any": ["warn"],
    },
  },
  {
    files: ["**/*.html"],
    extends: [
      ...angular.configs.templateRecommended,
      ...angular.configs.templateAccessibility,
    ],
    rules: {},
  }
);
