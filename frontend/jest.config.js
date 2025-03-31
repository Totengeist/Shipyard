module.exports = {
  preset: "jest-preset-angular",
  setupFilesAfterEnv: ["<rootDir>/setup-jest.ts"],
  coverageReporters: ['lcov', 'clover', 'text', 'html'],
  coverageDirectory: 'coverage',
  testPathIgnorePatterns: ["item_upload", "item_edit"],
}