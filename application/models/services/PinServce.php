<?php
/**
 * Created by PhpStorm.
 * User: intelWorx
 * Date: 09/01/2016
 * Time: 9:36 AM
 */

namespace models\services;


use models\entities\GeocodeCached;
use models\entities\Showtime;
use Valorin\PinPusher\Pin;
use Valorin\PinPusher\Pusher;

class PinServce extends \IdeoObject
{

    const DEFAULT_REMINDER_MINUTES = 30;

    /**
     * @var Showtime
     */
    private $showtime;
    private $reminderMinutes;
    private $userToken;

    /**
     * @var \DateTimeZone
     */
    private $dateTimeZone;
    /**
     * @var Pin
     */
    private $pin;

    /**
     * PinServce constructor.
     * @param int $showtimeId
     * @param $reminderMinutes
     * @param $userToken
     *
     * @param GeocodeCached $geocode
     */
    public function __construct($showtimeId, $reminderMinutes, $userToken, GeocodeCached $geocode)
    {
        $this->showtime = Showtime::findOne($showtimeId);
        if (!$this->showtime) {
            throw new \InvalidArgumentException("Specified showtime ID [{$showtimeId}] is not valid.");
        }

        $this->reminderMinutes = $reminderMinutes;
        $this->userToken = $userToken;
        $timezone = TimeZoneService::instance()->getTimeZone($geocode) ?: 'Europe/London';//default to UTC if all failes
        $this->dateTimeZone = new \DateTimeZone($timezone);
    }


    private function pinId()
    {
        return 'showtime-' . $this->showtime->id . '-' . substr($this->userToken, 0, 8);
    }

    /**
     * @param string $date date in yyyy-mm-dd form
     * @param string $time time in HH:MM form
     * @param int $offset in minutes
     * @return \DateTime
     */
    private function getDateTime($date, $time, $offset = 0)
    {
        $dateTime = new \DateTime("{$date} {$time}", $this->dateTimeZone);
        if ($offset) {
            $subtract = $offset < 0;
            $interval = new \DateInterval('PT' . abs($offset) . 'M');
            $dateTime = $subtract ? $dateTime->sub($interval) : $dateTime->add($interval);
        }

        \SystemLogger::info('Time: Generated', $date, $time, $dateTime->format('c'));
        return $dateTime;
    }

    private function generatePin()
    {

        $startDate = $this->getDateTime($this->showtime->show_date, $this->showtime->show_time);
        $this->pin = new Pin($this->pinId(), $startDate, $this->getPinLayout($startDate));
        $this->pin->setDuration((int)$this->showtime->movie->runtime);

        $this->setPinNotifications();
        $this->setPinReminder();
        $this->setPinActions();
    }

    private function getPinLayout(\DateTime $startTime)
    {
        $layout = new Pin\Layout\Calendar($this->showtime->movie->title);
        $layout->setLargeIcon(Pin\Icon::MOVIE_EVENT)
            ->setSmallIcon(Pin\Icon::MOVIE_EVENT)
            ->setTinyIcon(Pin\Icon::MOVIE_EVENT)
            ->setSubtitle($this->showtime->theatre->name)
            ->setBody("Starts by {$startTime->format('h:ia')}, at {$this->showtime->theatre->name}, {$this->showtime->theatre->address}");

        return $layout;
    }

    private function setPinNotifications()
    {
        $createNotification = new Pin\Notification\Generic("Showtime Pin Set", Pin\Icon::MOVIE_EVENT);
        $createNotification->setLargeIcon(Pin\Icon::MOVIE_EVENT)
            ->setBody("Timeline pin for {$this->showtime->movie->title} at {$this->showtime->theatre->name} has been created.");

        $updateNotification = new Pin\Notification\Generic("Showtime Pin Updated", Pin\Icon::MOVIE_EVENT);
        $updateNotification->setLargeIcon(Pin\Icon::MOVIE_EVENT)
            ->setBody("Timeline pin for {$this->showtime->movie->title} at {$this->showtime->theatre->name} has been updated.");

        $updateNotification->setTime(new \DateTime('now', $this->dateTimeZone));
        $this->pin->setCreateNotification($createNotification)
            ->setUpdateNotification($updateNotification);
    }

    private function setPinReminder()
    {
        $time = $this->getDateTime($this->showtime->show_date, $this->showtime->show_time, -$this->reminderMinutes);
        if ($this->reminderMinutes > 0) {
            $reminder = new Pin\Reminder\Generic($time, "Reminder: {$this->showtime->movie->title}", Pin\Icon::MOVIE_EVENT);

            $timeAp = $this->pin->getTime()->format('h:ia');
            $reminder->setBody("Hey, movie starts by {$timeAp}, at {$this->showtime->theatre->name}, {$this->showtime->theatre->address}")
                ->setLargeIcon(Pin\Icon::MOVIE_EVENT)
                ->setSmallIcon(Pin\Icon::MOVIE_EVENT);

            $this->pin->addReminder($reminder);
        }
    }


    private function setPinActions()
    {
        if ($this->showtime->url) {
            //create buy url
            $buyAction = new Pin\Action\OpenWatchApp("Buy Ticket", $this->showtime->id);
            $this->pin->addAction($buyAction);
        }
    }


    /**
     * @return Pin
     */
    public function getShowtimePin()
    {
        if (!$this->pin) {
            $this->generatePin();
        }

        return $this->pin;
    }


    /**
     *
     * Generates a pin and pushes it to a user.
     */
    public function pushPin()
    {
        if (!$this->pin) {
            $this->generatePin();
        }

        $pusher = new Pusher();
        $pusher->pushToUser($this->userToken, $this->pin);
    }
}