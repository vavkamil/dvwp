# Social Warfare Repository and Issue Tracker
This is a public repository for the Social Warfare WordPress plugin created primarily for the purpose of publishing and maintaining a public list of bugs, known issues, and feature requests with the community at large. Please use the "Issues" link above to track or add information to existing issues or to submit new issues altogether (see the guidelines below prior to adding a new issue).

***

## Guidelines & Standards for Creating & Updating Code on This Project

Coding Standards are an important factor for achieving a high code quality. A common visual style, naming conventions and other technical settings allow us to produce a homogenous code which is easy to read and maintain.

While this project has used various coding standards over time, this guide should provide the framework for all new code additions and to updates made to existing code.

### WordPress Coding Standards
All WordPress coding standards should be followed. Anything not specifically defined here should defer to WordPress's recommended coding standards found here: [WordPress Coding Standards](https://codex.wordpress.org/WordPress_Coding_Standards).

### Variables and Class Names
All PHP and Javascript variables, functions and classes, and CSS classes and ID's should follow the following naming standards. 

**PHP:** All names in PHP will use the Snake Case nomenclature. Independent function names will be preceded with the swp_ vendor prefix (e.g. swp_my_function_name). Methods and properties within classes do not require this prefix. 

Classes will be snake cased as well, but will also have the first letter of each word capitalized. Class names will also use the singular and never plural (e.g. My_Thing, not My_Things). The first letter will be capitalized on variables containing an instance of a class as well (e.g. $Class = new SWP_Class() ).

Line breaks in PHP will be used generously to make the code more easily readable. Two blank lines will be used after a function or method and before the beginning of the docblock for the next function or method. One blank line will appear at the end (but within) each standard dockblock. One blank line will appear immediately before any forward-slashed comments.

**Javascript:** In Javascript, we will use the camelCase nomenclature. 

**CSS:** CSS selectors will use the snake_case nomenclature just as in our PHP code.

### Conditionals and Loops
No inline/same-line conditionals or loops will be used, nor will we continue to use brace syntax. Rather we will use the colin/endif syntax.

### Style Guidelines for Docblocking Class Methods
Each file should begin with a docblock, as well as each function and class should be preceded with a docblock to explain it's purpose and functionality. There is no such thing as too much documentation on this project. The purpose is that any developer or even a non-developer should be able to easily browse each file and know exactly what is happening in that file.

In our experience, it is better to provide too much explanation than not enough. As such, we want to provide very thorough documentation for each function and method throughout the plugin.

The following will serve as an example docblock with instructions to follow.

```
1.     /**
2.      * Creates the default value for any new keys.
3.      *
4.      * @since  3.0.8  | 16 MAY 2018 | Created the method.
5.      * @since  3.0.8  | 24 MAY 2018 | Added check for order_of_icons
6.      * @since  3.1.0 | 13 JUN 2018 | Replaced array bracket notation.
7.      * @param  void
8.      * @return void
9.      *
10.    */
```
**Instructions:**

1. Every class method needs to contain a docblock immediately preceding it's declaration.
2. Each doblock should contain, at a minimum, a @_since, a @_param, and a @_return.
3. If either the @_param or @_return does not exist, it should be annotated with the word "void".
4. The @_since should always use the following format @_since x.x.x | DD MMM YYYY | Description
5. Every time a change is made to a method, a new @_since will be added logging that change.
6. If a method is anything other than public, it should have an @_access tag explaining why.
7. Two blank lines will precede each docblock.
8. One blank line within the docblock will be included at the end (see line 9 above).
9. No blank lines will be placed between the doblock and the declaration of the method.
10. A blank line will be placed between the method description and the @ tags.
11. If the title does not fit onto a single line (90 characters), it should be broken into a title and description separated by a blank line.

**Note:** Tags are preceded with an underscore to avoid tagging other GitHub users. Tags are not to be preceded with underscores in actual development.

***

## Guidelines for Submitting Issues to this GitHub Issue Tracker
Before submitting an issue to the issue tracker, please be sure of a few things. By following these guidelines, you maximize the possibility of our development team being able to find a solution to the issue in a quick and thorough manner.

### Prerequisites: Do this BEFORE submitting an issue

#### 1. Check the Documentation
First check the [support documentation](https://warfareplugins.com/support/) on the Warfare Plugins website to ensure that a solution to your issue has not already been addressed. Once you've determined that there is no useful information for your particular issue, you may proceed to step 2.

#### 2. Submit a Support Ticket First
Once you have completed step 1, you need to submit a ticket using the [contact form on the Warfare Plugins website](https://warfareplugins.com/). Once submitted, most tickets are responded to on the same or next business day so please allow until the end of the next business day for a response.

Many tickets being posted here are issues that simply require adjusting a single setting to accomodate themes or plugins in certain ways. The support team is able to respond to these much, much more quickly than here in the developer's workspace. Since this is the case, any issues submitted without first going through the support team will be deleted. Otherwise it is a waste of both your time and ours.

#### 3. Create an Issue on GitHub
Only once you have completed the above steps should you submit an issue to GitHub. GitHub is the workspace of the development team. In the rare instances where the support team is unable to solve a particular issue, it will be brought here to be tracked and solved by the development team. GitHub is public so that not only the support team, but also all users can view and track the progress of issues as well as commenting and participating in the conversation regarding each issue.

### Required Information: Provide this information WHILE submitting an issue
Once you've gone through support and you're ready to submit a GitHub issue, please **copy and paste the following information into your GitHub issue and fill out the blanks**:

#### Description of the Issue

A. Describe the nature of the issue:

B. How can this issue be replicated?:

C. Is this issue able to be viewed on your site right now? If so, where?

#### Additional Information

A. What version of the plugin are you using?

B. What version of WordPress are you using?

C. What caching plugin(s) are you using?

D. What version of PHP are you using?
