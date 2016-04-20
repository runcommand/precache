Feature: Precache themes

  Background:
    Given an empty directory
    And an empty cache

  Scenario: Precache a theme should precache theme install
    Given a WP install

    When I run `wp precache theme esteem`
    Then STDOUT should contain:
      """
      Esteem precached as
      """

    When I run `wp theme install esteem`
    Then STDOUT should contain:
      """
      Using cached
      """

  Scenario: Precache a theme with a specific version
    When I run `wp precache theme esteem --version=1.3.1`
    Then STDOUT should contain:
      """
      Downloading Esteem 1.3.1...
      Esteem precached as theme/esteem-1.3.1.zip
      Success: Theme(s) precached.
      """
    And the {SUITE_CACHE_DIR}/theme directory should contain:
      """
      esteem-1.3.1.zip
      """

