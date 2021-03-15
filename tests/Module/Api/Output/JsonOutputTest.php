<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);

namespace Ampache\Module\Api\Output;

use Ampache\MockeryTestCase;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\Model\Bookmark;
use Ampache\Repository\Model\Label;
use Ampache\Repository\Model\License;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Shoutbox;
use Ampache\Repository\Model\Tag;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Useractivity;
use Ampache\Repository\PodcastEpisodeRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;
use Mockery\MockInterface;

class JsonOutputTest extends MockeryTestCase
{
    /** @var MockInterface|ModelFactoryInterface */
    private MockInterface $modelFactory;

    /** @var MockInterface|AlbumRepositoryInterface */
    private MockInterface $albumRepository;

    /** @var MockInterface|SongRepositoryInterface */
    private MockInterface $songRepository;

    /** @var MockInterface|PodcastEpisodeRepositoryInterface */
    private MockInterface $podcastEpisodeRepository;

    private ?JsonOutput $subject;

    public function setUp(): void
    {
        $this->modelFactory             = $this->mock(ModelFactoryInterface::class);
        $this->albumRepository          = $this->mock(AlbumRepositoryInterface::class);
        $this->songRepository           = $this->mock(SongRepositoryInterface::class);
        $this->podcastEpisodeRepository = $this->mock(PodcastEpisodeRepositoryInterface::class);

        $this->subject = new JsonOutput(
            $this->modelFactory,
            $this->albumRepository,
            $this->songRepository,
            $this->podcastEpisodeRepository
        );
    }

    public function testShoutsReturnsResult(): void
    {
        $shoutId  = 666;
        $userId   = '42';
        $date     = 'some-date';
        $text     = 'some-text';
        $username = 'some-username';
        $result   = [
            'shout' => [[
                'id' => (string) $shoutId,
                'date' => $date,
                'text' => $text,
                'user' => [
                    'id' => $userId,
                    'username' => $username
                ]
            ]]
        ];

        $shout = $this->mock(Shoutbox::class);
        $user  = $this->mock(User::class);

        $shout->date    = $date;
        $shout->text    = $text;
        $shout->user    = $userId;
        $user->username = $username;

        $this->modelFactory->shouldReceive('createShoutbox')
            ->with($shoutId)
            ->once()
            ->andReturn($shout);
        $this->modelFactory->shouldReceive('createUser')
            ->with((int) $userId)
            ->once()
            ->andReturn($user);

        $this->assertSame(
            json_encode($result, JSON_PRETTY_PRINT),
            $this->subject->shouts([$shoutId])
        );
    }

    public function testUserReturnsFullInfo(): void
    {
        $user = $this->mock(User::class);

        $id              = 666;
        $username        = 'some-username';
        $fullname        = 'some-fullname';
        $apikey          = 'some-auth';
        $email           = 'some-email';
        $access          = 42;
        $fullname_public = 21;
        $validation      = 'some-validation';
        $disabled        = 33;
        $create_date     = 123;
        $last_seen       = 456;
        $website         = 'some-website';
        $state           = 'some-state';
        $city            = 'some-city';

        $user->id              = (string) $id;
        $user->username        = $username;
        $user->fullname        = $fullname;
        $user->apikey          = $apikey;
        $user->email           = $email;
        $user->access          = (string) $access;
        $user->fullname_public = (string) $fullname_public;
        $user->validation      = (string) $validation;
        $user->disabled        = (string) $disabled;
        $user->create_date     = (string) $create_date;
        $user->last_seen       = (string) $last_seen;
        $user->website         = $website;
        $user->state           = $state;
        $user->city            = $city;

        $user->shouldReceive('format')
            ->withNoArgs()
            ->once();

        $this->assertSame(
            json_encode(['user' => [
                'id' => (string) $id,
                'username' => $username,
                'auth' => $apikey,
                'email' => $email,
                'access' => $access,
                'fullname_public' => $fullname_public,
                'validation' => $validation,
                'disabled' => $disabled,
                'create_date' => $create_date,
                'last_seen' => $last_seen,
                'website' => $website,
                'state' => $state,
                'city' => $city,
                'fullname' => $fullname,
            ]], JSON_PRETTY_PRINT),
            $this->subject->user($user, true)
        );
    }

    public function testUserReturnsRestrictedInfo(): void
    {
        $user = $this->mock(User::class);

        $id              = 666;
        $username        = 'some-username';
        $create_date     = 123;
        $last_seen       = 456;
        $website         = 'some-website';
        $state           = 'some-state';
        $city            = 'some-city';

        $user->id              = (string) $id;
        $user->username        = $username;
        $user->create_date     = (string) $create_date;
        $user->last_seen       = (string) $last_seen;
        $user->website         = $website;
        $user->state           = $state;
        $user->city            = $city;

        $user->shouldReceive('format')
            ->withNoArgs()
            ->once();

        $this->assertSame(
            json_encode(['user' => [
                'id' => (string) $id,
                'username' => $username,
                'create_date' => $create_date,
                'last_seen' => $last_seen,
                'website' => $website,
                'state' => $state,
                'city' => $city,
            ]], JSON_PRETTY_PRINT),
            $this->subject->user($user, false)
        );
    }

    public function testGenresReturnsWrappedList(): void
    {
        $tagId           = 666;
        $albumCount      = 111;
        $artistCount     = 222;
        $songCount       = 333;
        $videoCount      = 444;
        $playlistCount   = 555;
        $liveStreamCount = 666;
        $name            = 'some-name';

        $tag = $this->mock(Tag::class);

        $this->modelFactory->shouldReceive('createTag')
            ->with($tagId)
            ->once()
            ->andReturn($tag);

        $tag->shouldReceive('count')
            ->withNoArgs()
            ->once()
            ->andReturn([
                'album' => (string) $albumCount,
                'artist' => (string) $artistCount,
                'song' => (string) $songCount,
                'video' => (string) $videoCount,
                'playlist' => (string) $playlistCount,
                'live_stream' => (string) $liveStreamCount,
            ]);

        $tag->name = $name;

        $this->assertSame(
            json_encode([
                'genre' => [[
                    'id' => (string) $tagId,
                    'name' => $name,
                    'albums' => $albumCount,
                    'artists' => $artistCount,
                    'songs' => $songCount,
                    'videos' => $videoCount,
                    'playlists' => $playlistCount,
                    'live_streams' => $liveStreamCount
                ]]
            ], JSON_PRETTY_PRINT),
            $this->subject->genres([$tagId])
        );
    }

    public function testGenresReturnsUnWrappedSingleItem(): void
    {
        $tagId           = 666;
        $albumCount      = 111;
        $artistCount     = 222;
        $songCount       = 333;
        $videoCount      = 444;
        $playlistCount   = 555;
        $liveStreamCount = 666;
        $name            = 'some-name';

        $tag = $this->mock(Tag::class);

        $this->modelFactory->shouldReceive('createTag')
            ->with($tagId)
            ->once()
            ->andReturn($tag);

        $tag->shouldReceive('count')
            ->withNoArgs()
            ->once()
            ->andReturn([
                'album' => (string) $albumCount,
                'artist' => (string) $artistCount,
                'song' => (string) $songCount,
                'video' => (string) $videoCount,
                'playlist' => (string) $playlistCount,
                'live_stream' => (string) $liveStreamCount,
            ]);

        $tag->name = $name;

        $this->assertSame(
            json_encode([
                'id' => (string) $tagId,
                'name' => $name,
                'albums' => $albumCount,
                'artists' => $artistCount,
                'songs' => $songCount,
                'videos' => $videoCount,
                'playlists' => $playlistCount,
                'live_streams' => $liveStreamCount

            ], JSON_PRETTY_PRINT),
            $this->subject->genres([$tagId, 999], false, 1)
        );
    }

    public function testSuccessReturnsData(): void
    {
        $value = 'some-value';
        $title = 'some-title';
        $data  = 'some-data';

        $this->assertSame(
            json_encode(['success' => $value, $title => $data], JSON_PRETTY_PRINT),
            $this->subject->success($value, [$title => $data])
        );
    }

    public function testLicensesReturnsList(): void
    {
        $licenseId   = 666;
        $name        = 'some-name';
        $description = 'some-description';
        $link        = 'some-link';

        $license = $this->mock(License::class);

        $this->modelFactory->shouldReceive('createLicense')
            ->with($licenseId)
            ->once()
            ->andReturn($license);

        $license->shouldReceive('getName')
            ->withNoArgs()
            ->once()
            ->andReturn($name);
        $license->shouldReceive('getDescription')
            ->withNoArgs()
            ->once()
            ->andReturn($description);
        $license->shouldReceive('getLink')
            ->withNoArgs()
            ->once()
            ->andReturn($link);

        $this->assertSame(
            json_encode([
                'license' => [[
                    'id' => (string) $licenseId,
                    'name' => $name,
                    'description' => $description,
                    'external_link' => $link,
                ]]
            ], JSON_PRETTY_PRINT),
            $this->subject->licenses([$licenseId])
        );
    }

    public function testLicensesReturnsSingleItem(): void
    {
        $licenseId   = 666;
        $name        = 'some-name';
        $description = 'some-description';
        $link        = 'some-link';

        $license = $this->mock(License::class);

        $this->modelFactory->shouldReceive('createLicense')
            ->with($licenseId)
            ->once()
            ->andReturn($license);

        $license->shouldReceive('getName')
            ->withNoArgs()
            ->once()
            ->andReturn($name);
        $license->shouldReceive('getDescription')
            ->withNoArgs()
            ->once()
            ->andReturn($description);
        $license->shouldReceive('getLink')
            ->withNoArgs()
            ->once()
            ->andReturn($link);

        $this->assertSame(
            json_encode([
                'id' => (string) $licenseId,
                'name' => $name,
                'description' => $description,
                'external_link' => $link,
            ], JSON_PRETTY_PRINT),
            $this->subject->licenses([$licenseId, 42], false, 1)
        );
    }

    public function testDictReturnsEncodedResult(): void
    {
        $data = ['key' => 'value'];

        $this->assertSame(
            json_encode($data, JSON_PRETTY_PRINT),
            $this->subject->dict($data)
        );
    }

    public function testBookmarksReturnsList(): void
    {
        $bookmarkId   = 666;
        $userName     = 'some-user-name';
        $objectType   = 'some-object-type';
        $objectId     = 'some-object-id';
        $position     = 'some-position';
        $comment      = 'some-comment';
        $creationDate = 12345;
        $updateDate   = 67890;

        $bookmark = $this->mock(Bookmark::class);

        $this->modelFactory->shouldReceive('createBookmark')
            ->with($bookmarkId)
            ->once()
            ->andReturn($bookmark);

        $bookmark->shouldReceive('getUserName')
            ->withNoArgs()
            ->once()
            ->andReturn($userName);
        $bookmark->object_type   = $objectType;
        $bookmark->object_id     = $objectId;
        $bookmark->position      = $position;
        $bookmark->comment       = $comment;
        $bookmark->creation_date = $creationDate;
        $bookmark->update_date   = $updateDate;

        $this->assertSame(
            json_encode(['bookmark' => [[
                'id' => (string) $bookmarkId,
                'owner' => $userName,
                'object_type' => $objectType,
                'object_id' => $objectId,
                'position' => $position,
                'client' => $comment,
                'creation_date' => $creationDate,
                'update_date' => $updateDate
            ]]], JSON_PRETTY_PRINT),
            $this->subject->bookmarks([42, $bookmarkId], 1, 1)
        );
    }

    public function testTimelineReturnsData(): void
    {
        $activityId   = 666;
        $userId       = 42;
        $activityDate = 123456;
        $objectType   = 'some-object-type';
        $objectId     = 33;
        $action       = 'some-action';
        $username     = 'some-username';

        $useractivity = $this->mock(Useractivity::class);
        $user         = $this->mock(User::class);

        $this->modelFactory->shouldReceive('createUseractivity')
            ->with($activityId)
            ->once()
            ->andReturn($useractivity);
        $this->modelFactory->shouldReceive('createUser')
            ->with($userId)
            ->once()
            ->andReturn($user);

        $useractivity->user          = $userId;
        $useractivity->activity_date = $activityDate;
        $useractivity->object_type   = $objectType;
        $useractivity->object_id     = $objectId;
        $useractivity->action        = $action;

        $user->username = $username;

        $this->assertSame(
            json_encode([
                'activity' => [[
                    'id' => (string) $activityId,
                    'date' => $activityDate,
                    'object_type' => $objectType,
                    'object_id' => $objectId,
                    'action' => $action,
                    'user' => [
                        'id' => (string) $userId,
                        'username' => $username
                    ]
                ]]
            ], JSON_PRETTY_PRINT),
            $this->subject->timeline([$activityId])
        );
    }

    public function testUsersReturnsList(): void
    {
        $userId   = 666;
        $username = 'some-username';

        $user = $this->mock(User::class);

        $user->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);
        $user->username = $username;

        $this->modelFactory->shouldReceive('createUser')
            ->with($userId)
            ->once()
            ->andReturn($user);

        $this->assertJsonOutput(
            ['user' => [[
                'id' => (string) $userId,
                'username' => $username
            ]]],
            $this->subject->users([$userId])
        );
    }

    public function testLabelsReturnsList(): void
    {
        $labelId1 = 666;
        $labelId2 = 42;

        $name     = 'some-name';
        $artists  = 'some-artists';
        $summary  = 'some-summary';
        $link     = 'some-link';
        $address  = 'some-address';
        $category = 'some-category';
        $email    = 'some-email';
        $website  = 'some-website';
        $user     = 'some-user';

        $label = $this->mock(Label::class);

        $label->shouldReceive('format')
            ->withNoArgs()
            ->once();

        $this->modelFactory->shouldReceive('createLabel')
            ->with($labelId2)
            ->once()
            ->andReturn($label);

        $label->f_name   = $name;
        $label->artists  = $artists;
        $label->summary  = $summary;
        $label->link     = $link;
        $label->address  = $address;
        $label->category = $category;
        $label->email    = $email;
        $label->website  = $website;
        $label->user     = $user;

        $this->assertJsonOutput(
            ['label' => [[
                'id' => (string) $labelId2,
                'name' => $name,
                'artists' => $artists,
                'summary' => $summary,
                'external_link' => $link,
                'address' => $address,
                'category' => $category,
                'email' => $email,
                'website' => $website,
                'user' => $user
            ]]],
            $this->subject->labels([$labelId1, $labelId2], true, 1, 1)
        );
    }

    private function assertJsonOutput(
        array $expectation,
        string $actual
    ): void {
        $this->assertSame(
            json_encode($expectation, JSON_PRETTY_PRINT),
            $actual
        );
    }

    public function testEmptyResultReturnsEmptyResult(): void
    {
        $type = 'some-type';

        $this->assertJsonOutput(
            [$type => []],
            $this->subject->emptyResult($type)
        );
    }
}
