@mod @mod_observation
Feature: Add a observation activity
  In order to conduct surveys of the users in a course
  As a teacher
  I need to add a observation activity to a moodle course

@javascript
  Scenario: Add a observation to a course without questions
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
    And the following "activities" exist:
      | activity | name | description | course | idnumber |
      | observation | Test observation | Test observation description | C1 | observation0 |
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test observation"
    Then I should see "This observation does not contain any questions."