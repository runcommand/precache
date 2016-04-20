Feature: Precache WordPress core

  Background:
    Given an empty directory
    And an empty cache

  Scenario: Precache core with a specific version
    When I run `wp precache core --version=4.4.2`
    Then STDOUT should contain:
      """
      Downloading WordPress 4.4.2 (en_US)...
      md5 hash verified: 65d89263dad6154fdc8b747e9ef4e357
      Success: WordPress pre-cached as core/wordpress-4.4.2-en_US.tar.gz
      """
    And the {SUITE_CACHE_DIR}/core directory should contain:
      """
      wordpress-4.4.2-en_US.tar.gz
      """
