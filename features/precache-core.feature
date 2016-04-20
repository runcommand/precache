Feature: Precache WordPress core

  Scenario: Precache core with a specific version
    Given a WP install

    When I run `wp precache core --version=4.4.2`
    Then STDOUT should contain:
      """
      Success: WordPress pre-cached.
      """
