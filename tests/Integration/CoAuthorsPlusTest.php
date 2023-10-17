<?php

namespace Automattic\CoAuthorsPlus\Tests\Integration;

class CoAuthorsPlusTest extends TestCase {

	private $author1;

	private $author2;

	private $author3;

	private $editor1;
	private $post;

	public function set_up() {

		parent::set_up();

		$this->author1 = $this->factory()->user->create_and_get(
			array(
				'role'       => 'author',
				'user_login' => 'author1',
			)
		);

		$this->author2 = $this->factory()->user->create_and_get(
			array(
				'role'       => 'author',
				'user_login' => 'author20',
			)
		);

		$this->author3 = $this->factory()->user->create_and_get(
			array(
				'role'       => 'author',
				'user_login' => 'author30',
			)
		);

		$this->editor1 = $this->factory()->user->create_and_get(
			array(
				'role'       => 'editor',
				'user_login' => 'editor1',
			)
		);

		$this->post = $this->factory()->post->create_and_get(
			array(
				'post_author'  => $this->author1->ID,
				'post_status'  => 'publish',
				'post_content' => rand_str(),
				'post_title'   => rand_str(),
				'post_type'    => 'post',
			)
		);
	}

	/**
	 * Checks whether the guest authors functionality is enabled or not.
	 *
	 * @covers CoAuthors_Plus::is_guest_authors_enabled()
	 */
	public function test_is_guest_authors_enabled() {

		global $coauthors_plus;

		$this->assertTrue( $coauthors_plus->is_guest_authors_enabled() );

		add_filter( 'coauthors_guest_authors_enabled', '__return_false' );

		$this->assertFalse( $coauthors_plus->is_guest_authors_enabled() );

		remove_filter( 'coauthors_guest_authors_enabled', '__return_false' );

		$this->assertTrue( $coauthors_plus->is_guest_authors_enabled() );
	}

	/**
	 * Checks coauthor object when he/she is a guest author.
	 *
	 * @covers CoAuthors_Plus::get_coauthor_by()
	 */
	public function test_get_coauthor_by_when_guest_author() {

		global $coauthors_plus;

		$guest_author_id = $coauthors_plus->guest_authors->create(
			array(
				'user_login'   => 'author2',
				'display_name' => 'author2',
			)
		);

		$coauthor = $coauthors_plus->get_coauthor_by( 'id', $guest_author_id );

		$this->assertInstanceOf( \stdClass::class, $coauthor );
		$this->assertObjectHasProperty( 'ID', $coauthor );
		$this->assertEquals( $guest_author_id, $coauthor->ID );
		$this->assertEquals( 'guest-author', $coauthor->type );
	}

	/**
	 * Checks coauthor object when he/she is a guest author with unicode user_login
	 *
	 * @covers CoAuthors_Plus::get_coauthor_by()
	 */
	public function test_get_coauthor_by_when_guest_author_has_unicode_username() {

		global $coauthors_plus;

		$user_login = 'محمود-الحسيني';
		$guest_author_id = $coauthors_plus->guest_authors->create(
			array(
				'user_login'	=> $user_login,
				'display_name'	=> 'محمود الحسيني',
			)
		);

		$coauthor = $coauthors_plus->get_coauthor_by( 'user_login', $user_login );

		$this->assertInstanceOf( \stdClass::class, $coauthor );
		$this->assertObjectHasProperty( 'ID', $coauthor );
		$this->assertEquals( $guest_author_id, $coauthor->ID );
		$this->assertEquals( 'guest-author', $coauthor->type );
	}

	/**
	 * Checks coauthor object when he/she is a wp author.
	 *
	 * @covers CoAuthors_Plus::get_coauthor_by()
	 */
	public function test_get_coauthor_by_when_guest_authors_not_enabled() {

		global $coauthors_plus;

		add_filter( 'coauthors_guest_authors_enabled', '__return_false' );

		$this->assertFalse( $coauthors_plus->get_coauthor_by( '', '' ) );

		$coauthor = $coauthors_plus->get_coauthor_by( 'id', $this->author1->ID );

		$this->assertInstanceOf( \WP_User::class, $coauthor );
		$this->assertObjectHasProperty( 'ID', $coauthor );
		$this->assertEquals( $this->author1->ID, $coauthor->ID );
		$this->assertEquals( 'wpuser', $coauthor->type );

		$coauthor = $coauthors_plus->get_coauthor_by( 'user_login', $this->author1->user_login );

		$this->assertInstanceOf( \WP_User::class, $coauthor );
		$this->assertObjectHasProperty( 'user_login', $coauthor->data );
		$this->assertEquals( $this->author1->user_login, $coauthor->user_login );

		$coauthor = $coauthors_plus->get_coauthor_by( 'user_nicename', $this->author1->user_nicename );

		$this->assertInstanceOf( \WP_User::class, $coauthor );
		$this->assertObjectHasProperty( 'user_nicename', $coauthor->data );
		$this->assertEquals( $this->author1->user_nicename, $coauthor->user_nicename );

		$coauthor = $coauthors_plus->get_coauthor_by( 'user_email', $this->author1->user_email );

		$this->assertInstanceOf( \WP_User::class, $coauthor );
		$this->assertObjectHasProperty( 'user_email', $coauthor->data );
		$this->assertEquals( $this->author1->user_email, $coauthor->user_email );

		remove_filter( 'coauthors_guest_authors_enabled', '__return_false' );

		$coauthors_plus->guest_authors->create_guest_author_from_user_id( $this->editor1->ID );

		$coauthor = $coauthors_plus->get_coauthor_by( 'id', $this->editor1->ID );

		$this->assertInstanceOf( \stdClass::class, $coauthor );
		$this->assertObjectHasProperty( 'linked_account', $coauthor );
		$this->assertEquals( $this->editor1->user_login, $coauthor->linked_account );
	}

	/**
	 * Checks coauthors plus is enabled for this post type.
	 *
	 * @covers CoAuthors_Plus::is_post_type_enabled()
	 */
	public function test_is_post_type_enabled() {

		global $coauthors_plus, $post;

		// Backing up global post.
		$post_backup = $post;

		// Checks when post type is null.
		$this->assertFalse( $coauthors_plus->is_post_type_enabled() );

		// Checks when post type is post.
		$this->assertTrue( $coauthors_plus->is_post_type_enabled( 'post' ) );

		// Checks when post type is page.
		$this->assertTrue( $coauthors_plus->is_post_type_enabled( 'page' ) );

		// Checks when post type is attachment.
		$this->assertFalse( $coauthors_plus->is_post_type_enabled( 'attachment' ) );

		// Checks when post type is revision.
		$this->assertFalse( $coauthors_plus->is_post_type_enabled( 'revision' ) );

		$post = $this->post;

		// Checks when post type set using global post.
		$this->assertTrue( $coauthors_plus->is_post_type_enabled() );

		$post   = '';
		$screen = get_current_screen();

		// Set the edit post current screen.
		set_current_screen( 'edit-post' );
		$this->assertTrue( $coauthors_plus->is_post_type_enabled() );

		$GLOBALS['current_screen'] = $screen;

		// Restore global post from backup.
		$post = $post_backup;
	}

	/**
	 * Checks if the current user can set co-authors or not using current screen.
	 *
	 * @covers CoAuthors_Plus::current_user_can_set_authors()
	 */
	public function test_current_user_can_set_author() {
		global $coauthors_plus;

		$this->assertFalse( $coauthors_plus->current_user_can_set_authors() );

		// Backing up current user.
		$original_user = get_current_user_id();

		// Checks when current user is author.
		wp_set_current_user( $this->author1->ID );

		$this->assertFalse( $coauthors_plus->current_user_can_set_authors() );

		// Checks when current user is editor.
		wp_set_current_user( $this->editor1->ID );

		$this->assertTrue( $coauthors_plus->current_user_can_set_authors() );

		// Checks when current user is admin.
		$admin1 = $this->factory()->user->create_and_get(
			array(
				'role' => 'administrator',
			)
		);

		wp_set_current_user( $admin1->ID );

		$this->assertTrue( $coauthors_plus->current_user_can_set_authors() );

		// Restore current user from backup.
		wp_set_current_user( $original_user );
	}

	/**
	 * Checks if the current user can set co-authors or not using coauthors_plus_edit_authors filter.
	 *
	 * @covers CoAuthors_Plus::current_user_can_set_authors()
	 */
	public function test_current_user_can_set_authors_using_coauthors_plus_edit_authors_filter() {

		global $coauthors_plus;

		// Backing up current user.
		$current_user = get_current_user_id();

		// Checking when current user is subscriber and filter is true/false.
		$subscriber1 = $this->factory()->user->create_and_get(
			array(
				'role' => 'subscriber',
			)
		);

		$this->assertFalse( $coauthors_plus->current_user_can_set_authors() );

		add_filter( 'coauthors_plus_edit_authors', '__return_true' );

		$this->assertTrue( $coauthors_plus->current_user_can_set_authors() );

		remove_filter( 'coauthors_plus_edit_authors', '__return_true' );

		// Checks when current user is editor.
		wp_set_current_user( $this->editor1->ID );

		$this->assertTrue( $coauthors_plus->current_user_can_set_authors() );

		add_filter( 'coauthors_plus_edit_authors', '__return_false' );

		$this->assertFalse( $coauthors_plus->current_user_can_set_authors() );

		remove_filter( 'coauthors_plus_edit_authors', '__return_false' );

		// Restore original user from backup.
		wp_set_current_user( $current_user );
	}

	/**
	 * Checks if the current user can edit a post they are set as a coauthor for.
	 */
	public function test_current_user_can_edit_post_they_coauthor() {
		global $coauthors_plus;

		// Backing up current user.
		$current_user = get_current_user_id();

		// Set up test post
		$admin_user = $this->factory()->user->create_and_get(
			array(
				'role'       => 'administrator',
				'user_login' => 'admin1',
			)
		);

		$post_id = $this->factory()->post->create(
			array(
				'post_author' => $admin_user->ID,
				'post_status' => 'publish',
				'post_type'   => 'post',
			)
		);


		// Checks when current user is author.
		wp_set_current_user( $this->author1->ID );

		// Author cannot edit by default.
		$this->assertFalse( current_user_can( 'edit_post', $post_id ) );

		// Author can editor when coauthor
		$coauthors_plus->add_coauthors( $post_id, array( $this->author1->user_login ) );
		$this->assertTrue( current_user_can( 'edit_post', $post_id ) );

		// Editor can edit by default
		$this->assertTrue( current_user_can( 'edit_post', $post_id ) );

		// Restore original user from backup.
		wp_set_current_user( $current_user );

	}

	/**
	 * Checks matching co-authors based on a search value when no arguments provided.
	 *
	 * @covers CoAuthors_Plus::search_authors()
	 */
	public function test_search_authors_no_args() {

		global $coauthors_plus;

		// Checks when search term is empty.
		$authors = $coauthors_plus->search_authors();

		$this->assertNotEmpty( $authors );
		$this->assertArrayHasKey( 'admin', $authors );
		$this->assertArrayHasKey( $this->author1->user_login, $authors );
		$this->assertArrayHasKey( $this->editor1->user_login, $authors );

		// Checks when search term is empty and any subscriber exists.
		$subscriber1 = $this->factory()->user->create_and_get(
			array(
				'role'       => 'subscriber',
				'user_login' => 'subscriber1',
			)
		);

		$authors = $coauthors_plus->search_authors();

		$this->assertNotEmpty( $authors );
		$this->assertArrayNotHasKey( $subscriber1->user_login, $authors );

		// Checks when search term is empty and any contributor exists.
		$contributor1 = $this->factory()->user->create_and_get(
			array(
				'role'       => 'contributor',
				'user_login' => 'contributor1',
			)
		);

		$authors = $coauthors_plus->search_authors();

		$this->assertNotEmpty( $authors );
		$this->assertArrayHasKey( $contributor1->user_login, $authors );
	}

	/**
	 * Checks matching co-authors based on a search value when only search keyword is provided.
	 *
	 * @covers CoAuthors_Plus::search_authors()
	 */
	public function test_search_authors_when_search_keyword_provided() {

		global $coauthors_plus;

		// Checks when author does not exist with searched term.
		$this->assertEmpty( $coauthors_plus->search_authors( 'test' ) );

		// Checks when author searched using ID.
		$authors = $coauthors_plus->search_authors( $this->author1->ID );

		$this->assertNotEmpty( $authors );
		$this->assertArrayHasKey( $this->author1->user_login, $authors );
		$this->assertArrayNotHasKey( $this->editor1->user_login, $authors );
		$this->assertArrayNotHasKey( 'admin', $authors );

		// Checks when author searched using display_name.
		$authors = $coauthors_plus->search_authors( $this->author1->display_name );

		$this->assertNotEmpty( $authors );
		$this->assertArrayHasKey( $this->author1->user_login, $authors );
		$this->assertArrayNotHasKey( $this->editor1->user_login, $authors );
		$this->assertArrayNotHasKey( 'admin', $authors );

		// Checks when author searched using user_email.
		$authors = $coauthors_plus->search_authors( $this->author1->user_email );

		$this->assertNotEmpty( $authors );
		$this->assertArrayHasKey( $this->author1->user_login, $authors );
		$this->assertArrayNotHasKey( $this->editor1->user_login, $authors );
		$this->assertArrayNotHasKey( 'admin', $authors );

		// Checks when author searched using user_login.
		$authors = $coauthors_plus->search_authors( $this->author1->user_login );

		$this->assertNotEmpty( $authors );
		$this->assertArrayHasKey( $this->author1->user_login, $authors );
		$this->assertArrayNotHasKey( $this->editor1->user_login, $authors );
		$this->assertArrayNotHasKey( 'admin', $authors );

		// Checks when any subscriber exists using ID but not author.
		$subscriber1 = $this->factory()->user->create_and_get(
			array(
				'role' => 'subscriber',
			)
		);

		$this->assertEmpty( $coauthors_plus->search_authors( $subscriber1->ID ) );
	}

	/**
	 * Checks matching co-authors based on a search value when only ignore authors are provided.
	 *
	 * @covers CoAuthors_Plus::search_authors()
	 */
	public function test_search_authors_when_ignored_authors_provided() {

		global $coauthors_plus;

		// Ignoring single author.
		$ignored_authors = array( $this->author1->user_nicename );

		$authors = $coauthors_plus->search_authors( '', $ignored_authors );

		$this->assertNotEmpty( $authors );
		$this->assertArrayNotHasKey( $this->author1->user_login, $authors );

		// Checks when ignoring author1 but also exists one more author with similar kind of data.
		$author2 = $this->factory()->user->create_and_get(
			array(
				'role'       => 'author',
				'user_login' => 'author2',
			)
		);

		$authors = $coauthors_plus->search_authors( '', $ignored_authors );

		$this->assertNotEmpty( $authors );
		$this->assertArrayNotHasKey( $this->author1->user_login, $authors );
		$this->assertArrayHasKey( $author2->user_login, $authors );

		// Ignoring multiple authors.
		$authors = $coauthors_plus->search_authors( '', array( $this->author1->user_nicename, $author2->user_nicename ) );

		$this->assertNotEmpty( $authors );
		$this->assertArrayNotHasKey( $this->author1->user_login, $authors );
		$this->assertArrayNotHasKey( $author2->user_login, $authors );
	}

	/**
	 * Checks matching co-authors based on a search value when search keyword as well as ignore authors are provided.
	 *
	 * @covers CoAuthors_Plus::search_authors()
	 */
	public function test_search_authors_when_search_keyword_and_ignored_authors_provided() {

		global $coauthors_plus;

		// Checks when ignoring author1.
		$ignored_authors = array( $this->author1->user_nicename );

		$this->assertEmpty( $coauthors_plus->search_authors( $this->author1->ID, $ignored_authors ) );

		// Checks when ignoring author1 but also exists one more author with similar kind of data.
		$author2 = $this->factory()->user->create_and_get(
			array(
				'role'       => 'author',
				'user_login' => 'author2',
			)
		);

		$authors = $coauthors_plus->search_authors( 'author', $ignored_authors );

		$this->assertNotEmpty( $authors );
		$this->assertArrayNotHasKey( $this->author1->user_login, $authors );
		$this->assertArrayHasKey( $author2->user_login, $authors );
	}

	/**
	 * Checks the author term for a given co-author when passed co-author is not an object.
	 *
	 * @covers CoAuthors_Plus::get_author_term()
	 */
	public function test_get_author_term_when_coauthor_is_not_object() {

		global $coauthors_plus;

		$this->assertEmpty( $coauthors_plus->get_author_term( '' ) );
		$this->assertEmpty( $coauthors_plus->get_author_term( $this->author1->ID ) );
		$this->assertEmpty( $coauthors_plus->get_author_term( (array) $this->author1 ) );
	}

	/**
	 * Checks the author term for a given co-author using cache.
	 *
	 * @covers CoAuthors_Plus::get_author_term()
	 */
	public function test_get_author_term_using_caching() {

		global $coauthors_plus;

		$cache_key = 'author-term-' . $this->author1->user_nicename;

		// Checks when term does not exist in cache.
		$this->assertFalse( wp_cache_get( $cache_key, 'co-authors-plus' ) );

		// Checks when term exists in cache.
		$author_term        = $coauthors_plus->get_author_term( $this->author1 );
		$author_term_cached = wp_cache_get( $cache_key, 'co-authors-plus' );

		$this->assertInstanceOf( \WP_Term::class, $author_term );
		$this->assertEquals( $author_term, $author_term_cached );
	}

	/**
	 * Checks the author term for a given co-author with having linked account.
	 *
	 * @covers CoAuthors_Plus::get_author_term()
	 */
	public function test_get_author_term_when_author_has_linked_account() {

		global $coauthors_plus;

		// Checks when term exists using linked account.
		$coauthor_id = $coauthors_plus->guest_authors->create_guest_author_from_user_id( $this->editor1->ID );
		$coauthor    = $coauthors_plus->get_coauthor_by( 'id', $coauthor_id );

		$author_term = $coauthors_plus->get_author_term( $coauthor );

		$this->assertInstanceOf( \WP_Term::class, $author_term );

		// Checks when term does not exist or deleted somehow.
		wp_delete_term( $author_term->term_id, $author_term->taxonomy );

		$this->assertFalse( $coauthors_plus->get_author_term( $coauthor ) );
	}

	/**
	 * Checks the author term for a given co-author without having linked account.
	 *
	 * @covers CoAuthors_Plus::get_author_term()
	 */
	public function test_get_author_term_when_author_has_not_linked_account() {

		global $coauthors_plus;

		// Checks when term exists without linked account.
		$coauthor_id = $coauthors_plus->guest_authors->create(
			array(
				'display_name' => 'guest',
				'user_login'   => 'guest',
			)
		);
		$coauthor    = $coauthors_plus->get_coauthor_by( 'id', $coauthor_id );

		$author_term = $coauthors_plus->get_author_term( $coauthor );

		$this->assertInstanceOf( \WP_Term::class, $author_term );

		// Checks when term does not exist or deleted somehow.
		wp_delete_term( $author_term->term_id, $author_term->taxonomy );

		$this->assertFalse( $coauthors_plus->get_author_term( $coauthor ) );
	}

	/**
	 * Checks update author term when passed coauthor is not an object.
	 *
	 * @covers CoAuthors_Plus::update_author_term()
	 */
	public function test_update_author_term_when_coauthor_is_not_object() {

		global $coauthors_plus;

		$this->assertEmpty( $coauthors_plus->update_author_term( '' ) );
		$this->assertEmpty( $coauthors_plus->update_author_term( $this->author1->ID ) );
		$this->assertEmpty( $coauthors_plus->update_author_term( (array) $this->author1 ) );
	}

	/**
	 * Checks update author term when author term exists for passed coauthor.
	 *
	 * @covers CoAuthors_Plus::update_author_term()
	 */
	public function test_update_author_term_when_author_term_exists() {

		global $coauthors_plus;

		// Checks term description.
		$author_term = $coauthors_plus->update_author_term( $this->author1 );

		// In "update_author_term()", only description is being updated, so asserting that only ( here and everywhere ).
		$this->assertEquals( $this->author1->display_name . ' ' . $this->author1->first_name . ' ' . $this->author1->last_name . ' ' . $this->author1->user_login . ' ' . $this->author1->ID . ' ' . $this->author1->user_email, $author_term->description );

		// Checks term description after updating user.
		wp_update_user(
			array(
				'ID'         => $this->author1->ID,
				'first_name' => 'author1',
			)
		);

		$author_term = $coauthors_plus->update_author_term( $this->author1 );

		$this->assertEquals( $this->author1->display_name . ' ' . $this->author1->first_name . ' ' . $this->author1->last_name . ' ' . $this->author1->user_login . ' ' . $this->author1->ID . ' ' . $this->author1->user_email, $author_term->description );

		// Backup coauthor taxonomy.
		$taxonomy_backup = $coauthors_plus->coauthor_taxonomy;

		wp_update_user(
			array(
				'ID'        => $this->author1->ID,
				'last_name' => 'author1',
			)
		);

		// Checks with different taxonomy.
		$coauthors_plus->coauthor_taxonomy = 'abcd';

		$this->assertFalse( $coauthors_plus->update_author_term( $this->author1 ) );

		// Restore coauthor taxonomy from backup.
		$coauthors_plus->coauthor_taxonomy = $taxonomy_backup;
	}

	/**
	 * Checks update author term when author term does not exist for passed coauthor.
	 *
	 * @covers CoAuthors_Plus::update_author_term()
	 */
	public function test_update_author_term_when_author_term_not_exist() {

		global $coauthors_plus;

		// Checks term description.
		$author_term = $coauthors_plus->update_author_term( $this->editor1 );

		$this->assertEquals( $this->editor1->display_name . ' ' . $this->editor1->first_name . ' ' . $this->editor1->last_name . ' ' . $this->editor1->user_login . ' ' . $this->editor1->ID . ' ' . $this->editor1->user_email, $author_term->description );

		// Checks term description after updating user.
		wp_update_user(
			array(
				'ID'         => $this->editor1->ID,
				'first_name' => 'editor1',
			)
		);

		$author_term = $coauthors_plus->update_author_term( $this->editor1 );

		$this->assertEquals( $this->editor1->display_name . ' ' . $this->editor1->first_name . ' ' . $this->editor1->last_name . ' ' . $this->editor1->user_login . ' ' . $this->editor1->ID . ' ' . $this->editor1->user_email, $author_term->description );

		// Backup coauthor taxonomy.
		$taxonomy_backup = $coauthors_plus->coauthor_taxonomy;

		wp_update_user(
			array(
				'ID'        => $this->editor1->ID,
				'last_name' => 'editor1',
			)
		);

		// Checks with different taxonomy.
		$coauthors_plus->coauthor_taxonomy = 'abcd';

		$this->assertFalse( $coauthors_plus->update_author_term( $this->editor1 ) );

		// Restore coauthor taxonomy from backup.
		$coauthors_plus->coauthor_taxonomy = $taxonomy_backup;
	}

	/**
	 * This is a basic test to ensure that any authors being assigned to a post
	 * using the CoAuthors_Plus::add_coauthors() method are appropriately
	 * associated to the post. Some of the things the add_coauthors()
	 * method should do are:
	 *
	 * 1. Ensure that the post_author is set to the first author in the list
	 * 2. This is done internally by calling CoAuthors_Plus::get_coauthor_by(),
	 * which should return a WP_User in this instance (since the author is not linked to a coauthor account)
	 * 3. Since this coauthor is not linked, create the author's coauthor term, and associate it to the post.
	 *
	 * @return void
	 */
	public function test_assign_post_author_from_author_who_has_not_been_linked() {
		$post = $this->factory()->post->create_and_get(
			array(
				'post_author' => $this->author2->ID,
				'post_status' => 'publish',
				'post_type'   => 'post',
			)
		);
		$post_id = $post->ID;

		$first_added_authors = $this->_cap->add_coauthors( $post_id, array( $this->author3->user_login ) );
		$this->assertTrue( $first_added_authors );

		$query2 = new WP_Query(
			array(
				'p' => $post_id
			)
		);

		$this->assertEquals( 1, $query2->found_posts );
		$this->assertEquals( $this->author3->ID, $query2->posts[0]->post_author );

		$author3_term = $this->_cap->get_author_term( $this->author3 );

		$this->assertInstanceOf( WP_Term::class, $author3_term );

		$post_author_terms = wp_get_post_terms( $post_id, $this->_cap->coauthor_taxonomy );

		$this->assertIsArray( $post_author_terms );
		$this->assertCount( 1, $post_author_terms );
		$this->assertInstanceOf( WP_Term::class, $post_author_terms[0] );
		$this->assertEquals( 'cap-' . $this->author3->user_login, $post_author_terms[0]->slug );

		// Confirming that now $author2 does have an author term
		$second_added_authors = $this->_cap->add_coauthors( $post_id, array( $this->author2->user_login ) );
		$this->assertTrue( $second_added_authors );
		$author2_term = $this->_cap->get_author_term( $this->author2 );
		$this->assertInstanceOf( WP_Term::class, $author2_term );

		$post_author_terms = wp_get_post_terms( $post_id, $this->_cap->coauthor_taxonomy );

		$this->assertIsArray( $post_author_terms );
		$this->assertCount( 1, $post_author_terms );
		$this->assertInstanceOf( WP_Term::class, $post_author_terms[0] );
		$this->assertEquals( 'cap-' . $this->author2->user_login, $post_author_terms[0]->slug );
	}

	/**
	 * This test should not affect the post_author field, since we
	 * are simply appending an author to a post.
	 *
	 * @return void
	 */
	public function test_append_post_author_who_has_not_been_linked(  ) {
		$post_id = $this->factory()->post->create(
			array(
				'post_author' => $this->author2->ID,
				'post_status' => 'publish',
				'post_type'   => 'post',
			)
		);

		$this->_cap->add_coauthors( $post_id, array( $this->author3->user_login ), true );

		$query = new WP_Query(
			array(
				'p' => $post_id,
			)
		);

		$this->assertEquals( 1, $query->found_posts );
		$this->assertEquals( $this->author2->ID, $query->posts[0]->post_author );

		$post_author_terms = wp_get_post_terms( $post_id, $this->_cap->coauthor_taxonomy );

		$this->assertIsArray( $post_author_terms );
		$this->assertCount( 2, $post_author_terms );

		$author_slugs = array(
			'cap-' . $this->author2->user_login,
			'cap-' . $this->author3->user_login,
		);

		foreach ( $post_author_terms as $term ) {
			$this->assertInstanceOf( WP_Term::class, $term );
			$this->assertContains( $term->slug, $author_slugs );
		}
	}

	/**
	 * Here we are assigning multiple authors who have not been
	 * linked to a coauthor to a post. Since we are not
	 * appending authors to the post, we should
	 * expect the post_author to change.
	 *
	 * @return void
	 */
	public function test_assign_post_authors_from_authors_who_have_not_been_linked() {
		$post_id = $this->factory()->post->create(
			array(
				'post_author' => $this->author1->ID,
				'post_status' => 'publish',
				'post_type'   => 'post',
			)
		);

		$this->_cap->add_coauthors(
			$post_id,
			array(
				$this->author3->user_login,
				$this->editor1->user_login,
				$this->author2->user_login,
			)
		);

		$query = new WP_Query(
			array(
				'p' => $post_id,
			)
		);

		$this->assertEquals( 1, $query->found_posts );
		$this->assertEquals( $this->author3->ID, $query->posts[0]->post_author );

		$post_author_terms = wp_get_post_terms( $post_id, $this->_cap->coauthor_taxonomy );

		$this->assertIsArray( $post_author_terms );
		$this->assertCount( 3, $post_author_terms );

		$author_slugs = array(
			'cap-' . $this->author3->user_login,
			'cap-' . $this->editor1->user_login,
			'cap-' . $this->author2->user_login,
		);

		foreach ( $post_author_terms as $term ) {
			$this->assertInstanceOf( WP_Term::class, $term );
			$this->assertContains( $term->slug, $author_slugs );
		}
	}

	/**
	 * Here we are creating guest authors (coauthors) and assigning them to a post,
	 * which was created by a WP_User. Since the guest authors have not been
	 * linked to a WP_User, the wp_post.post_author column should not
	 * change, and the response from CoAuthors_Plus::add_coauthors()
	 * should be false, since no WP_User could be found.
	 *
	 * @return void
	 */
	public function test_assign_post_authors_from_coauthors_who_have_not_been_linked() {
		$random_username = 'random_user_' . rand( 1, 1000 );
		$display_name = str_replace( '_', ' ', $random_username );

		$guest_author_1_id = $this->_cap->guest_authors->create(
			array(
				'user_login'   => $random_username,
				'display_name' => $display_name,
			)
		);
		/**
		 * By using CoAuthors_Plus::get_coauthor_by(), we are ensuring
		 * that the recent changes to the code will prioritize
		 * returning a Guest Author when one is found.
		 */
		$guest_author_1 = $this->_cap->get_coauthor_by( 'id', $guest_author_1_id );

		$this->assertIsObject( $guest_author_1 );
		$this->assertThat(
			$guest_author_1,
			$this->logicalNot(
				$this->isInstanceOf( WP_User::class )
			)
		);

		$random_username = 'random_user_' . rand( 1001, 2000 );
		$display_name = str_replace( '_', ' ', $random_username );

		$guest_author_2_id = $this->_cap->guest_authors->create(
			array(
				'user_login'   => $random_username,
				'display_name' => $display_name,
			)
		);
		$guest_author_2 = $this->_cap->get_coauthor_by( 'id', $guest_author_2_id );

		$this->assertIsObject( $guest_author_2 );
		$this->assertThat(
			$guest_author_2,
			$this->logicalNot(
				$this->isInstanceOf( WP_User::class )
			)
		);

		$post_id = $this->factory()->post->create(
			array(
				'post_author' => $this->author1->ID,
				'post_status' => 'publish',
				'post_type'   => 'post',
			)
		);

		$query = new WP_Query(
			array(
				'p' => $post_id,
			)
		);

		$this->assertEquals( 1, $query->found_posts );
		$this->assertEquals( $this->author1->ID, $query->posts[0]->post_author );

		$result = $this->_cap->add_coauthors(
			$post_id,
			array(
				$guest_author_1->user_login,
				$guest_author_2->user_login,
			)
		);

		/*
		 * This is false because we are NOT appending any coauthors who are linked to a WP_User to the post.
		 * */
		$this->assertFalse( $result );

		$second_query = new WP_Query(
			array(
				'p' => $post_id,
			)
		);

		$this->assertEquals( 1, $second_query->found_posts );
		$this->assertEquals( $this->author1->ID, $second_query->posts[0]->post_author );
	}

	/**
	 * This test is similar to above, but instead here, we are appending coauthors.
	 * This means that the wp_posts.post_author column is not expected to change,
	 * and so the response from CoAuthors_Plus::add_coauthors() should be true.
	 *
	 * @return void
	 */
	public function test_append_post_authors_from_coauthors_who_have_not_been_linked() {
		$random_username = 'random_user_' . rand( 1, 1000 );
		$display_name = str_replace( '_', ' ', $random_username );

		$guest_author_1_id = $this->_cap->guest_authors->create(
			array(
				'user_login'   => $random_username,
				'display_name' => $display_name,
			)
		);
		$guest_author_1 = $this->_cap->get_coauthor_by( 'id', $guest_author_1_id );

		$this->assertIsObject( $guest_author_1 );
		$this->assertThat(
			$guest_author_1,
			$this->logicalNot(
				$this->isInstanceOf( WP_User::class )
			)
		);

		$random_username = 'random_user_' . rand( 1001, 2000 );
		$display_name = str_replace( '_', ' ', $random_username );

		$guest_author_2_id = $this->_cap->guest_authors->create(
			array(
				'user_login'   => $random_username,
				'display_name' => $display_name,
			)
		);
		$guest_author_2 = $this->_cap->get_coauthor_by( 'id', $guest_author_2_id );

		$this->assertIsObject( $guest_author_2 );
		$this->assertThat(
			$guest_author_2,
			$this->logicalNot(
				$this->isInstanceOf( WP_User::class )
			)
		);

		$post_id = $this->factory()->post->create(
			array(
				'post_author' => $this->author1->ID,
				'post_status' => 'publish',
				'post_type'   => 'post',
			)
		);

		$query = new WP_Query(
			array(
				'p' => $post_id,
			)
		);

		$this->assertEquals( 1, $query->found_posts );
		$this->assertEquals( $this->author1->ID, $query->posts[0]->post_author );

		$result = $this->_cap->add_coauthors(
			$post_id,
			array(
				$guest_author_1->user_login,
				$guest_author_2->user_login,
			),
			true
		);

		$this->assertTrue( $result );

		$second_query = new WP_Query(
			array(
				'p' => $post_id,
			)
		);

		$this->assertEquals( 1, $second_query->found_posts );
		$this->assertEquals( $this->author1->ID, $second_query->posts[0]->post_author );

		$post_author_terms = wp_get_post_terms( $post_id, $this->_cap->coauthor_taxonomy );

		$this->assertIsArray( $post_author_terms );
		$this->assertCount( 3, $post_author_terms );

		$author_slugs = array(
			'cap-' . $this->author1->user_login,
			'cap-' . $guest_author_1->user_login,
			'cap-' . $guest_author_2->user_login,
		);

		foreach ( $post_author_terms as $term ) {
			$this->assertInstanceOf( WP_Term::class, $term );
			$this->assertContains( $term->slug, $author_slugs );
		}
	}

	/**
	 * Here we are assigning one coauthor and one WP_User who have not been linked.
	 * The result should be true, since the WP_User will be assigned as the
	 * post_author. There should only be 2 WP_Terms for the authors.
	 *
	 * @return void
	 */
	public function test_assign_coauthors_from_coauthors_and_user_who_have_not_been_linked() {
		$random_username = 'random_user_' . rand( 1, 1000 );
		$display_name = str_replace( '_', ' ', $random_username );

		$guest_author_1_id = $this->_cap->guest_authors->create(
			array(
				'user_login'   => $random_username,
				'display_name' => $display_name,
			)
		);
		$guest_author_1 = $this->_cap->get_coauthor_by( 'id', $guest_author_1_id );

		$this->assertIsObject( $guest_author_1 );
		$this->assertThat(
			$guest_author_1,
			$this->logicalNot(
				$this->isInstanceOf( WP_User::class )
			)
		);

		$post_id = $this->factory()->post->create(
			array(
				'post_author' => $this->author1->ID,
				'post_status' => 'publish',
				'post_type'   => 'post',
			)
		);

		$query = new WP_Query(
			array(
				'p' => $post_id,
			)
		);

		$this->assertEquals( 1, $query->found_posts );
		$this->assertEquals( $this->author1->ID, $query->posts[0]->post_author );

		$result = $this->_cap->add_coauthors(
			$post_id,
			array(
				$guest_author_1->user_login,
				$this->author3->user_login,
			)
		);

		$this->assertTrue( $result );

		$second_query = new WP_Query(
			array(
				'p' => $post_id,
			)
		);

		$this->assertEquals( 1, $second_query->found_posts );
		$this->assertEquals( $this->author3->ID, $second_query->posts[0]->post_author );

		$post_author_terms = wp_get_post_terms( $post_id, $this->_cap->coauthor_taxonomy );

		$this->assertIsArray( $post_author_terms );
		$this->assertCount( 2, $post_author_terms );

		$author_slugs = array(
			'cap-' . $this->author3->user_login,
			'cap-' . $guest_author_1->user_login,
		);

		foreach ( $post_author_terms as $term ) {
			$this->assertInstanceOf( WP_Term::class, $term );
			$this->assertContains( $term->slug, $author_slugs );
		}
	}

	/**
	 * Similar to above, but we are appending instead. The wp_posts.post_author should
	 * not be changed, but we should see 3 WP_Terms for the authors now.
	 *
	 * @return void
	 */
	public function test_append_coauthors_from_coauthors_and_user_who_have_not_been_linked() {
		$random_username = 'random_user_' . rand( 1, 1000 );
		$display_name = str_replace( '_', ' ', $random_username );

		$guest_author_1_id = $this->_cap->guest_authors->create(
			array(
				'user_login'   => $random_username,
				'display_name' => $display_name,
			)
		);
		$guest_author_1 = $this->_cap->get_coauthor_by( 'id', $guest_author_1_id );

		$this->assertIsObject( $guest_author_1 );
		$this->assertThat(
			$guest_author_1,
			$this->logicalNot(
				$this->isInstanceOf( WP_User::class )
			)
		);

		$post_id = $this->factory()->post->create(
			array(
				'post_author' => $this->author1->ID,
				'post_status' => 'publish',
				'post_type'   => 'post',
			)
		);

		$query = new WP_Query(
			array(
				'p' => $post_id,
			)
		);

		$this->assertEquals( 1, $query->found_posts );
		$this->assertEquals( $this->author1->ID, $query->posts[0]->post_author );

		$result = $this->_cap->add_coauthors(
			$post_id,
			array(
				$guest_author_1->user_login,
				$this->author3->user_login,
			),
			true
		);

		$this->assertTrue( $result );

		$second_query = new WP_Query(
			array(
				'p' => $post_id,
			)
		);

		$this->assertEquals( 1, $second_query->found_posts );
		$this->assertEquals( $this->author1->ID, $second_query->posts[0]->post_author );

		$post_author_terms = wp_get_post_terms( $post_id, $this->_cap->coauthor_taxonomy );

		$this->assertIsArray( $post_author_terms );
		$this->assertCount( 3, $post_author_terms );

		$author_slugs = array(
			'cap-' . $this->author1->user_login,
			'cap-' . $this->author3->user_login,
			'cap-' . $guest_author_1->user_login,
		);

		foreach ( $post_author_terms as $term ) {
			$this->assertInstanceOf( WP_Term::class, $term );
			$this->assertContains( $term->slug, $author_slugs );
		}
	}

	/**
	 * This is where we test many moving parts of the CoAuthorsPlugin all at once. We are creating a guest author from a
	 * WP_User, and then assigning that guest author to a post. Since the guest author is linked to a WP_User, the
	 * function CoAuthors_Plus::get_coauthor_by() should return a guest author object along with meta data
	 * indicating that the object is linked to a WP_User. The wp_posts.post_author column should change,
	 * and the response from CoAuthors_Plus::add_coauthors() should be true.
	 * @return void
	 */
	public function test_assign_post_authors_from_coauthors_who_are_linked() {
		$this->_cap->guest_authors->create_guest_author_from_user_id( $this->author2->ID );
		$this->_cap->guest_authors->create_guest_author_from_user_id( $this->author3->ID );

		$linked_author_1 = $this->_cap->get_coauthor_by( 'id', $this->author2->ID );
		$this->assertIsObject( $linked_author_1 );
		$this->assertThat(
			$linked_author_1,
			$this->logicalNot(
				$this->isInstanceOf( WP_User::class )
			)
		);
		$this->assertTrue( property_exists( $linked_author_1, 'type' ) );
		$this->assertEquals( 'guest-author', $linked_author_1->type );
		$this->assertTrue( property_exists( $linked_author_1, 'is_wp_user' ) );
		$this->assertTrue( $linked_author_1->is_wp_user );
		$this->assertTrue( property_exists( $linked_author_1, 'wp_user' ) );
		$this->assertInstanceOf( WP_User::class, $linked_author_1->wp_user );
		$this->assertEquals( $this->author2->ID, $linked_author_1->wp_user->ID );

		$linked_author_2 = $this->_cap->get_coauthor_by( 'user_login', $this->author3->user_login );
		$this->assertIsObject( $linked_author_2 );
		$this->assertThat(
			$linked_author_2,
			$this->logicalNot(
				$this->isInstanceOf( WP_User::class )
			)
		);
		$this->assertTrue( property_exists( $linked_author_2, 'type' ) );
		$this->assertEquals( 'guest-author', $linked_author_2->type );
		$this->assertTrue( property_exists(  $linked_author_2, 'is_wp_user' ) );
		$this->assertTrue( $linked_author_2->is_wp_user );
		$this->assertTrue( property_exists( $linked_author_2, 'wp_user' ) );
		$this->assertInstanceOf( WP_User::class, $linked_author_2->wp_user );
		$this->assertEquals( $this->author3->ID, $linked_author_2->wp_user->ID );

		$post_id = $this->factory()->post->create(
			array(
				'post_author' => $this->editor1->ID,
				'post_status' => 'publish',
				'post_type'   => 'post',
			)
		);

		$query = new WP_Query(
			array(
				'p' => $post_id,
			)
		);

		$this->assertEquals( 1, $query->found_posts );
		$this->assertEquals( $this->editor1->ID, $query->posts[0]->post_author );

		$result = $this->_cap->add_coauthors(
			$post_id,
			array(
				$linked_author_1->user_login,
				$linked_author_2->user_login,
			)
		);

		$this->assertTrue( $result );

		$second_query = new WP_Query(
			array(
				'p' => $post_id,
			)
		);

		$this->assertEquals( 1, $second_query->found_posts );
		$this->assertEquals( $this->author2->ID, $second_query->posts[0]->post_author );

		$post_author_terms = wp_get_post_terms( $post_id, $this->_cap->coauthor_taxonomy );

		$this->assertIsArray( $post_author_terms );
		$this->assertCount( 2, $post_author_terms );

		$author_slugs = array(
			'cap-' . $this->author2->user_login,
			'cap-' . $this->author3->user_login,
		);

		foreach ( $post_author_terms as $term ) {
			$this->assertInstanceOf( WP_Term::class, $term );
			$this->assertContains( $term->slug, $author_slugs );
		}
	}

	/**
	 * Very similar test as before. The only difference is that we are appending,
	 * so the wp_posts.post_author column should not change, but we should
	 * see 3 WP_Terms for the authors now.
	 *
	 * @return void
	 */
	public function test_append_post_authors_from_coauthors_one_of_whom_is_linked() {
		$random_username = 'random_user_' . rand( 1, 1000 );
		$display_name = str_replace( '_', ' ', $random_username );

		$guest_author_1_id = $this->_cap->guest_authors->create(
			array(
				'user_login'   => $random_username,
				'display_name' => $display_name,
			)
		);
		$guest_author_1 = $this->_cap->get_coauthor_by( 'id', $guest_author_1_id );

		$this->assertIsObject( $guest_author_1 );
		$this->assertThat(
			$guest_author_1,
			$this->logicalNot(
				$this->isInstanceOf( WP_User::class )
			)
		);

		$this->_cap->guest_authors->create_guest_author_from_user_id( $this->author3->ID );

		$linked_author_1 = $this->_cap->get_coauthor_by( 'id', $this->author3->ID );
		$this->assertIsObject( $linked_author_1 );
		$this->assertThat(
			$linked_author_1,
			$this->logicalNot(
				$this->isInstanceOf( WP_User::class )
			)
		);
		$this->assertTrue( property_exists( $linked_author_1, 'type' ) );
		$this->assertEquals( 'guest-author', $linked_author_1->type );
		$this->assertTrue( property_exists( $linked_author_1, 'is_wp_user' ) );
		$this->assertTrue( $linked_author_1->is_wp_user );
		$this->assertTrue( property_exists( $linked_author_1, 'wp_user' ) );
		$this->assertInstanceOf( WP_User::class, $linked_author_1->wp_user );
		$this->assertEquals( $this->author3->ID, $linked_author_1->wp_user->ID );

		$post_id = $this->factory()->post->create(
			array(
				'post_author' => $this->editor1->ID,
				'post_status' => 'publish',
				'post_type'   => 'post',
			)
		);

		$query = new WP_Query(
			array(
				'p' => $post_id,
			)
		);

		$this->assertEquals( 1, $query->found_posts );
		$this->assertEquals( $this->editor1->ID, $query->posts[0]->post_author );

		$result = $this->_cap->add_coauthors(
			$post_id,
			array(
				$guest_author_1->user_login,
				$linked_author_1->user_login,
			),
			true
		);

		$this->assertTrue( $result );

		$second_query = new WP_Query(
			array(
				'p' => $post_id,
			)
		);

		$this->assertEquals( 1, $second_query->found_posts );
		$this->assertEquals( $this->editor1->ID, $second_query->posts[0]->post_author );

		$post_author_terms = wp_get_post_terms( $post_id, $this->_cap->coauthor_taxonomy );

		$this->assertIsArray( $post_author_terms );
		$this->assertCount( 3, $post_author_terms );

		$author_slugs = array(
			'cap-' . $this->editor1->user_login,
			'cap-' . $guest_author_1->user_login,
			'cap-' . $this->author3->user_login,
		);

		foreach ( $post_author_terms as $term ) {
			$this->assertInstanceOf( WP_Term::class, $term );
			$this->assertContains( $term->slug, $author_slugs );
		}
	}

	/**
	 * This is testing that multiple coauthors can be correctly assigned to a post.
	 * The wp_posts.post_author column should be set to the first WP_User in
	 * the array, which is $author1. The response from CoAuthors_Plus::add_coauthors()
	 * should be true, and there should be 3 author terms associated with the post.
	 *
	 * @return void
	 */
	public function test_assign_multiple_post_authors_wp_user_guest_author_linked_user(  ) {
		$random_username = 'random_user_' . rand( 1, 1000 );
		$display_name = str_replace( '_', ' ', $random_username );

		$guest_author_1_id = $this->_cap->guest_authors->create(
			array(
				'user_login'   => $random_username,
				'display_name' => $display_name,
			)
		);
		$guest_author_1 = $this->_cap->get_coauthor_by( 'id', $guest_author_1_id );

		$this->assertIsObject( $guest_author_1 );
		$this->assertThat(
			$guest_author_1,
			$this->logicalNot(
				$this->isInstanceOf( WP_User::class )
			)
		);

		$this->_cap->guest_authors->create_guest_author_from_user_id( $this->author3->ID );

		$linked_author_1 = $this->_cap->get_coauthor_by( 'id', $this->author3->ID );
		$this->assertIsObject( $linked_author_1 );
		$this->assertThat(
			$linked_author_1,
			$this->logicalNot(
				$this->isInstanceOf( WP_User::class )
			)
		);

		$post_id = $this->factory()->post->create(
			array(
				'post_author' => $this->editor1->ID,
				'post_status' => 'publish',
				'post_type'   => 'post',
			)
		);

		$query = new WP_Query(
			array(
				'p' => $post_id,
			)
		);

		$this->assertEquals( 1, $query->found_posts );
		$this->assertEquals( $this->editor1->ID, $query->posts[0]->post_author );

		$result = $this->_cap->add_coauthors(
			$post_id,
			array(
				$this->author1->user_login,
				$guest_author_1->user_login,
				$linked_author_1->user_login,
			)
		);

		$this->assertTrue( $result );

		$second_query = new WP_Query(
			array(
				'p' => $post_id,
			)
		);

		$this->assertEquals( 1, $second_query->found_posts );
		$this->assertEquals( $this->author1->ID, $second_query->posts[0]->post_author );

		$post_author_terms = wp_get_post_terms( $post_id, $this->_cap->coauthor_taxonomy );

		$this->assertIsArray( $post_author_terms );
		$this->assertCount( 3, $post_author_terms );

		$author_slugs = array(
			'cap-' . $this->author1->user_login,
			'cap-' . $guest_author_1->user_login,
			'cap-' . $this->author3->user_login,
		);

		foreach ( $post_author_terms as $term ) {
			$this->assertInstanceOf( WP_Term::class, $term );
			$this->assertContains( $term->slug, $author_slugs );
		}
	}

	/**
	 * With this test we are confirming that no matter the order in which a linked user is
	 * passed in the array, the wp_posts.post_author column will be set to the linked user.
	 *
	 * @return void
	 */
	public function test_assign_multiple_post_authors_only_one_linked_passed_last() {
		$random_username = 'random_user_' . rand( 1, 1000 );
		$display_name = str_replace( '_', ' ', $random_username );

		$guest_author_1_id = $this->_cap->guest_authors->create(
			array(
				'user_login'   => $random_username,
				'display_name' => $display_name,
			)
		);
		$guest_author_1 = $this->_cap->get_coauthor_by( 'id', $guest_author_1_id );

		$this->assertIsObject( $guest_author_1 );
		$this->assertThat(
			$guest_author_1,
			$this->logicalNot(
				$this->isInstanceOf( WP_User::class )
			)
		);

		$random_username = 'random_user_' . rand( 1001, 2000 );
		$display_name = str_replace( '_', ' ', $random_username );

		$guest_author_2_id = $this->_cap->guest_authors->create(
			array(
				'user_login'   => $random_username,
				'display_name' => $display_name,
			)
		);
		$guest_author_2 = $this->_cap->get_coauthor_by( 'id', $guest_author_2_id );

		$this->assertIsObject( $guest_author_2 );
		$this->assertThat(
			$guest_author_2,
			$this->logicalNot(
				$this->isInstanceOf( WP_User::class )
			)
		);

		$this->_cap->guest_authors->create_guest_author_from_user_id( $this->author3->ID );

		$linked_author_1 = $this->_cap->get_coauthor_by( 'id', $this->author3->ID );
		$this->assertIsObject( $linked_author_1 );
		$this->assertThat(
			$linked_author_1,
			$this->logicalNot(
				$this->isInstanceOf( WP_User::class )
			)
		);
		$this->assertTrue( property_exists( $linked_author_1, 'is_wp_user' ) );
		$this->assertTrue( $linked_author_1->is_wp_user );
		$this->assertTrue( property_exists( $linked_author_1, 'wp_user' ) );
		$this->assertInstanceOf( WP_User::class, $linked_author_1->wp_user );
		$this->assertEquals( $this->author3->ID, $linked_author_1->wp_user->ID );

		$post_id = $this->factory()->post->create(
			array(
				'post_author' => $this->editor1->ID,
				'post_status' => 'publish',
				'post_type'   => 'post',
			)
		);

		$query = new WP_Query(
			array(
				'p' => $post_id,
			)
		);

		$this->assertEquals( 1, $query->found_posts );
		$this->assertEquals( $this->editor1->ID, $query->posts[0]->post_author );

		$result = $this->_cap->add_coauthors(
			$post_id,
			array(
				$guest_author_1->user_login,
				$guest_author_2->user_login,
				// Linked user is passed last in array.
				// Placement within array should not matter.
				// It should get picked up, and used to set wp_posts.post_author
				$linked_author_1->user_login,
			)
		);

		$this->assertTrue( $result );

		$second_query = new WP_Query(
			array(
				'p' => $post_id,
			)
		);

		$this->assertEquals( 1, $second_query->found_posts );
		$this->assertEquals( $this->author3->ID, $second_query->posts[0]->post_author );

		$post_author_terms = wp_get_post_terms( $post_id, $this->_cap->coauthor_taxonomy );

		$this->assertIsArray( $post_author_terms );
		$this->assertCount( 3, $post_author_terms );

		$author_slugs = array(
			'cap-' . $this->author3->user_login,
			'cap-' . $guest_author_1->user_login,
			'cap-' . $guest_author_2->user_login,
		);

		foreach ( $post_author_terms as $term ) {
			$this->assertInstanceOf( WP_Term::class, $term );
			$this->assertContains( $term->slug, $author_slugs );
		}
	}

	/**
	 * Same as above, except we will pass a WP_User before the linked user. The wp_posts.post_author
	 * should be set to the WP_User, and there should be 3 WP_Terms for the authors.
	 *
	 * @return void
	 */
	public function test_assign_multiple_post_authors_one_user_before_one_linked_passed_last() {
		$random_username = 'random_user_' . rand( 1, 1000 );
		$display_name = str_replace( '_', ' ', $random_username );

		$guest_author_1_id = $this->_cap->guest_authors->create(
			array(
				'user_login'   => $random_username,
				'display_name' => $display_name,
			)
		);
		$guest_author_1 = $this->_cap->get_coauthor_by( 'id', $guest_author_1_id );

		$this->assertIsObject( $guest_author_1 );
		$this->assertThat(
			$guest_author_1,
			$this->logicalNot(
				$this->isInstanceOf( WP_User::class )
			)
		);

		$this->_cap->guest_authors->create_guest_author_from_user_id( $this->author3->ID );

		$linked_author_1 = $this->_cap->get_coauthor_by( 'id', $this->author3->ID );
		$this->assertIsObject( $linked_author_1 );
		$this->assertThat(
			$linked_author_1,
			$this->logicalNot(
				$this->isInstanceOf( WP_User::class )
			)
		);
		$this->assertTrue( property_exists( $linked_author_1, 'is_wp_user' ) );
		$this->assertTrue( $linked_author_1->is_wp_user );
		$this->assertTrue( property_exists( $linked_author_1, 'wp_user' ) );
		$this->assertInstanceOf( WP_User::class, $linked_author_1->wp_user );
		$this->assertEquals( $this->author3->ID, $linked_author_1->wp_user->ID );

		$post_id = $this->factory()->post->create(
			array(
				'post_author' => $this->editor1->ID,
				'post_status' => 'publish',
				'post_type'   => 'post',
			)
		);

		$query = new WP_Query(
			array(
				'p' => $post_id,
			)
		);

		$this->assertEquals( 1, $query->found_posts );
		$this->assertEquals( $this->editor1->ID, $query->posts[0]->post_author );

		$result = $this->_cap->add_coauthors(
			$post_id,
			array(
				$guest_author_1->user_login,
				$this->author2->user_login,
				$linked_author_1->user_login,
			)
		);

		$this->assertTrue( $result );

		$second_query = new WP_Query(
			array(
				'p' => $post_id,
			)
		);

		$this->assertEquals( 1, $second_query->found_posts );
		$this->assertEquals( $this->author2->ID, $second_query->posts[0]->post_author );

		$post_author_terms = wp_get_post_terms( $post_id, $this->_cap->coauthor_taxonomy );

		$this->assertIsArray( $post_author_terms );
		$this->assertCount( 3, $post_author_terms );

		$author_slugs = array(
			'cap-' . $this->author2->user_login,
			'cap-' . $this->author3->user_login,
			'cap-' . $guest_author_1->user_login,
		);

		foreach ( $post_author_terms as $term ) {
			$this->assertInstanceOf( WP_Term::class, $term );
			$this->assertContains( $term->slug, $author_slugs );
		}
	}

	/**
	 * Same as above, except not the linked user is passed up front. This should result in
	 * the wp_posts.post_author column being set to the linked user, and there should be
	 * 3 WP_Terms for the authors.
	 *
	 * @return void
	 */
	public function test_assign_multiple_post_authors_one_linked_passed_first() {
		$random_username = 'random_user_' . rand( 1, 1000 );
		$display_name = str_replace( '_', ' ', $random_username );

		$guest_author_1_id = $this->_cap->guest_authors->create(
			array(
				'user_login'   => $random_username,
				'display_name' => $display_name,
			)
		);
		$guest_author_1 = $this->_cap->get_coauthor_by( 'id', $guest_author_1_id );

		$this->assertIsObject( $guest_author_1 );
		$this->assertThat(
			$guest_author_1,
			$this->logicalNot(
				$this->isInstanceOf( WP_User::class )
			)
		);

		$this->_cap->guest_authors->create_guest_author_from_user_id( $this->author3->ID );

		$linked_author_1 = $this->_cap->get_coauthor_by( 'id', $this->author3->ID );
		$this->assertIsObject( $linked_author_1 );
		$this->assertThat(
			$linked_author_1,
			$this->logicalNot(
				$this->isInstanceOf( WP_User::class )
			)
		);
		$this->assertTrue( property_exists( $linked_author_1, 'is_wp_user' ) );
		$this->assertTrue( $linked_author_1->is_wp_user );
		$this->assertTrue( property_exists( $linked_author_1, 'wp_user' ) );
		$this->assertInstanceOf( WP_User::class, $linked_author_1->wp_user );
		$this->assertEquals( $this->author3->ID, $linked_author_1->wp_user->ID );

		$post_id = $this->factory()->post->create(
			array(
				'post_author' => $this->editor1->ID,
				'post_status' => 'publish',
				'post_type'   => 'post',
			)
		);

		$query = new WP_Query(
			array(
				'p' => $post_id,
			)
		);

		$this->assertEquals( 1, $query->found_posts );
		$this->assertEquals( $this->editor1->ID, $query->posts[0]->post_author );

		$result = $this->_cap->add_coauthors(
			$post_id,
			array(
				$linked_author_1->user_login,
				$this->author2->user_login,
				$guest_author_1->user_login,
			)
		);

		$this->assertTrue( $result );

		$second_query = new WP_Query(
			array(
				'p' => $post_id,
			)
		);

		$this->assertEquals( 1, $second_query->found_posts );
		$this->assertEquals( $this->author3->ID, $second_query->posts[0]->post_author );

		$post_author_terms = wp_get_post_terms( $post_id, $this->_cap->coauthor_taxonomy );

		$this->assertIsArray( $post_author_terms );
		$this->assertCount( 3, $post_author_terms );

		$author_slugs = array(
			'cap-' . $this->author2->user_login,
			'cap-' . $this->author3->user_login,
			'cap-' . $guest_author_1->user_login,
		);

		foreach ( $post_author_terms as $term ) {
			$this->assertInstanceOf( WP_Term::class, $term );
			$this->assertContains( $term->slug, $author_slugs );
		}
	}

	/**
	 * Here we are testing a similar scenario as above, except we are passing the linked user's WP_User login field
	 * instead when assigning the post authors. The result should be that the correct GA account
	 * is located and used to set the author's for the post.
	 *
	 * @return void
	 */
	public function test_assign_multiple_post_authors_one_linked_passed_using_user_login_to_assign() {
		$random_username = 'random_user_' . rand( 1, 1000 );
		$display_name = str_replace( '_', ' ', $random_username );

		$guest_author_1_id = $this->_cap->guest_authors->create(
			array(
				'user_login'   => $random_username,
				'display_name' => $display_name,
			)
		);
		$guest_author_1 = $this->_cap->get_coauthor_by( 'id', $guest_author_1_id );

		$this->assertIsObject( $guest_author_1 );
		$this->assertThat(
			$guest_author_1,
			$this->logicalNot(
				$this->isInstanceOf( WP_User::class )
			)
		);

		$this->_cap->guest_authors->create_guest_author_from_user_id( $this->author3->ID );

		$linked_author_1 = $this->_cap->get_coauthor_by( 'id', $this->author3->ID );
		$this->assertIsObject( $linked_author_1 );
		$this->assertThat(
			$linked_author_1,
			$this->logicalNot(
				$this->isInstanceOf( WP_User::class )
			)
		);
		$this->assertTrue( property_exists( $linked_author_1, 'is_wp_user' ) );
		$this->assertTrue( $linked_author_1->is_wp_user );
		$this->assertTrue( property_exists( $linked_author_1, 'wp_user' ) );
		$this->assertInstanceOf( WP_User::class, $linked_author_1->wp_user );
		$this->assertEquals( $this->author3->ID, $linked_author_1->wp_user->ID );

		$post_id = $this->factory()->post->create(
			array(
				'post_author' => $this->editor1->ID,
				'post_status' => 'publish',
				'post_type'   => 'post',
			)
		);

		$query = new WP_Query(
			array(
				'p' => $post_id,
			)
		);

		$this->assertEquals( 1, $query->found_posts );
		$this->assertEquals( $this->editor1->ID, $query->posts[0]->post_author );

		$result = $this->_cap->add_coauthors(
			$post_id,
			array(
				$guest_author_1->user_login,
				// This should be converted to the corresponding GA account for linked user.
				$this->author3->user_login,
				$this->author2->user_login,
			)
		);

		$this->assertTrue( $result );

		$second_query = new WP_Query(
			array(
				'p' => $post_id,
			)
		);

		$this->assertEquals( 1, $second_query->found_posts );
		$this->assertEquals( $this->author3->ID, $second_query->posts[0]->post_author );

		$post_author_terms = wp_get_post_terms( $post_id, $this->_cap->coauthor_taxonomy );

		$this->assertIsArray( $post_author_terms );
		$this->assertCount( 3, $post_author_terms );

		$author_slugs = array(
			'cap-' . $this->author2->user_login,
			'cap-' . $this->author3->user_login,
			'cap-' . $guest_author_1->user_login,
		);

		foreach ( $post_author_terms as $term ) {
			$this->assertInstanceOf( WP_Term::class, $term );
			$this->assertContains( $term->slug, $author_slugs );
		}

		$guest_author_term = wp_get_post_terms( $linked_author_1->ID, $this->_cap->coauthor_taxonomy );

		$this->assertIsArray( $guest_author_term );
		$this->assertCount( 1, $guest_author_term );
		$this->assertEquals( 'cap-' . $linked_author_1->user_login, $guest_author_term[0]->slug );
	}

	/**
	 * This test is a combination of a few different aspects that establish some expected plugin behavior.
	 * Two posts are created to test with. Both have no WP_User as the author (i.e. wp_posts.post_author = 0).
	 * For the first post:
	 * 1. A guest author is created, and assigned to the post.
	 * 2. Since there is no WP_User which is passed, and the GA is NOT being appended, the result should be false.
	 * 3. The wp_posts.post_author column should still be 0 since this is a GA and not a WP_User or a linked GA.
	 *
	 * For the second post:
	 * 1. A guest author is created, and appended to the post.
	 * 2. Since we are appending a coauthor, it does not matter if there was already a WP_User author, so result should be true.
	 * 3. The wp_posts.post_author column should still be 0 since this is a GA and not a WP_User or a linked GA.
	 *
	 * Going back to the first post:
	 * 1. A linked user and a WP_User are created and assigned to this post.
	 * 2. Result should be true since here we essentially have 2 WP_Users.
	 * 3. Since there is a WP_User, and it is passed first, the wp_posts.post_author column should be set to the ID for that WP_User.
	 * 4. There should only be 2 author terms for this post, one for the WP_User, and one for the linked account.
	 * The term for the GA which was previously assigned is deleted.
	 *
	 * Finally, going back to the second post:
	 * 1. A linked user and a WP_User are created and appended to this post.
	 * 2. Result should be true since we have 2 WP_Users.
	 * 3. Here we passed the linked author first, so the wp_posts.post_author column should match the ID for the linked WP_user account.
	 * 4. There should be 3 author terms for this post, one for the GA, the WP_user, and linked account.
	 * @return void
	 */
	public function test_assign_post_authors_from_post_with_no_author() {
		$post_id_1 = $this->factory()->post->create(
			array(
				'post_author' => 0,
				'post_status' => 'publish',
				'post_type'   => 'post',
			)
		);

		$query = new WP_Query(
			array(
				'p' => $post_id_1,
			)
		);

		$this->assertEquals( 1, $query->found_posts );
		$this->assertEquals( 0, $query->posts[0]->post_author );

		$post_id_2 = $this->factory()->post->create(
			array(
				'post_author' => 0,
				'post_status' => 'publish',
				'post_type'   => 'post',
			)
		);

		$second_post_query = new WP_Query(
			array(
				'p' => $post_id_2,
			)
		);

		$this->assertEquals( 1, $second_post_query->found_posts );
		$this->assertEquals( 0, $second_post_query->posts[0]->post_author );

		$random_username = 'random_user_' . rand( 90000, 100000 );
		$display_name = str_replace( '_', ' ', $random_username );

		$guest_author_1_id = $this->_cap->guest_authors->create(
			array(
				'user_login'   => $random_username,
				'display_name' => $display_name,
			)
		);
		$guest_author_1 = $this->_cap->get_coauthor_by( 'id', $guest_author_1_id );

		$this->assertIsObject( $guest_author_1 );
		$this->assertThat(
			$guest_author_1,
			$this->logicalNot(
				$this->isInstanceOf( WP_User::class )
			)
		);

		$this->_cap->guest_authors->create_guest_author_from_user_id( $this->author3->ID );

		$linked_author_1 = $this->_cap->get_coauthor_by( 'id', $this->author3->ID );
		$this->assertIsObject( $linked_author_1 );
		$this->assertThat(
			$linked_author_1,
			$this->logicalNot(
				$this->isInstanceOf( WP_User::class )
			)
		);
		$this->assertTrue( property_exists( $linked_author_1, 'is_wp_user' ) );
		$this->assertTrue( $linked_author_1->is_wp_user );
		$this->assertTrue( property_exists( $linked_author_1, 'wp_user' ) );
		$this->assertInstanceOf( WP_User::class, $linked_author_1->wp_user );
		$this->assertEquals( $this->author3->ID, $linked_author_1->wp_user->ID );

		$result = $this->_cap->add_coauthors(
			$post_id_1,
			array(
				$guest_author_1->user_login,
			)
		);

		$this->assertFalse( $result );

		$post_1_author_terms = wp_get_post_terms( $post_id_1, $this->_cap->coauthor_taxonomy );

		$this->assertIsArray( $post_1_author_terms );
		$this->assertCount( 1, $post_1_author_terms );
		$this->assertEquals( 'cap-' . $guest_author_1->user_login, $post_1_author_terms[0]->slug );

		$third_post_query = new WP_Query(
			array(
				'p' => $post_id_1,
			)
		);

		$this->assertEquals( 1, $third_post_query->found_posts );
		$this->assertEquals( 0, $third_post_query->posts[0]->post_author );

		$result = $this->_cap->add_coauthors(
			$post_id_2,
			array(
				$guest_author_1->user_login,
			),
			true
		);

		$this->assertTrue( $result );

		$fourth_post_query = new WP_Query(
			array(
				'p' => $post_id_2,
			)
		);

		$this->assertEquals( 1, $fourth_post_query->found_posts );
		$this->assertEquals( 0, $fourth_post_query->posts[0]->post_author );

		$result = $this->_cap->add_coauthors(
			$post_id_1,
			array(
				$this->author2->user_login,
				$linked_author_1->user_login,
			)
		);

		$this->assertTrue( $result );

		$fifth_post_query = new WP_Query(
			array(
				'p' => $post_id_1,
			)
		);

		$this->assertEquals( 1, $fifth_post_query->found_posts );
		$this->assertEquals( $this->author2->ID, $fifth_post_query->posts[0]->post_author );

		$post_author_terms = wp_get_post_terms( $post_id_1, $this->_cap->coauthor_taxonomy );

		$this->assertIsArray( $post_author_terms );
		$this->assertCount( 2, $post_author_terms );

		$author_slugs = array(
			'cap-' . $this->author2->user_login,
			'cap-' . $linked_author_1->user_login,
		);

		foreach ( $post_author_terms as $term ) {
			$this->assertInstanceOf( WP_Term::class, $term );
			$this->assertContains( $term->slug, $author_slugs );
		}

		$result = $this->_cap->add_coauthors(
			$post_id_2,
			array(
				$linked_author_1->user_login,
				$this->author2->user_login,
			),
			true
		);

		$this->assertTrue( $result );

		$sixth_post_query = new WP_Query(
			array(
				'p' => $post_id_2,
			)
		);

		$this->assertEquals( 1, $sixth_post_query->found_posts );
		$this->assertEquals( $this->author3->ID, $sixth_post_query->posts[0]->post_author );

		$post_author_terms = wp_get_post_terms( $post_id_2, $this->_cap->coauthor_taxonomy );

		$this->assertIsArray( $post_author_terms );
		$this->assertCount( 3, $post_author_terms );

		$author_slugs = array(
			'cap-' . $guest_author_1->user_login,
			'cap-' . $this->author2->user_login,
			'cap-' . $linked_author_1->user_login,
		);

		foreach ( $post_author_terms as $term ) {
			$this->assertInstanceOf( WP_Term::class, $term );
			$this->assertContains( $term->slug, $author_slugs );
		}
	}

	/**
	 * @covers CoAuthors_Plus::is_block_editor()
	 */
	public function test_is_block_editor() {
		global $coauthors_plus;

		set_current_screen( 'post-new.php' );

		$this->assertTrue( $coauthors_plus->is_block_editor() );

		set_current_screen( 'wp-login.php' );

		$this->assertFalse( $coauthors_plus->is_block_editor() );
	}

	/**
	 * @covers CoAuthors_Plus::enqueue_sidebar_plugin_assets()
	 */
	public function test_enqueue_editor_assets() {

		// Default state
		do_action( 'enqueue_block_editor_assets' );

		$this->assertFalse( wp_script_is( 'coauthors-sidebar-js' ) );
		$this->assertFalse( wp_style_is( 'coauthors-sidebar-css' ) );

		// Enabled post type and user who can edit, feature not enabled
		wp_set_current_user( $this->editor1->ID );
		set_current_screen( 'edit-post' );

		do_action( 'enqueue_block_editor_assets' );

		$this->assertTrue( wp_script_is( 'coauthors-sidebar-js' ) );
		$this->assertTrue( wp_style_is( 'coauthors-sidebar-css' ) );

	}

	/**
	 * @covers CoAuthors_Plus::add_coauthors_box()
	 */
	public function test_add_coauthors_box() {
		global $coauthors_plus, $wp_meta_boxes;

		wp_set_current_user( $this->editor1->ID );
		set_current_screen( 'post-new.php' );

		$coauthors_plus->add_coauthors_box();

		$this->assertNull( $wp_meta_boxes, 'Failed to assert the coauthors metabox is not added when the block editor is loaded.' );

	}

	/**
	 * Test the expected default supported post types.
	 */
	public function test_default_supported_post_types() {
		$supported_post_types = (new \CoAuthors_Plus())->supported_post_types();
		$expected = array(
			'post',
			'page',
		);
		$this->assertEquals( array_values( $expected ), array_values( $supported_post_types ) );
	}

	/**
	 * Test whether the supported post types can be filtered.
	 */
	public function test_can_filter_supported_post_types() {
		// This should be detected.
		$post_type_with_author = register_post_type(
			'foo',
			array(
				'supports' => array( 'author' ),
			)
		);

		// This doesn't support the author, so should not be detected.
		$post_type_without_author = register_post_type(
			'bar',
			array(
				'supports' => array( 'title' ),
			)
		);

		$callback = function( $post_types ) {
			$key = array_search( 'page', $post_types, true );
			unset( $post_types[ $key ] );

			return $post_types;
		};
		add_filter( 'coauthors_supported_post_types', $callback );

		$supported_post_types = ( new \CoAuthors_Plus() )->supported_post_types();

		$expected = array(
			'post',
			'foo',
		);
		$this->assertEquals( array_values( $expected ), array_values( $supported_post_types ) );

		// Clean up.
		remove_filter( 'coauthors_supported_post_types', $callback );
		unregister_post_type( 'foo' );
	}
}