# Playing By Ear Game Systems

This document summarizes the game systems, data flow, and safeguards discussed
for the Clickteam Fusion game and its Railway/PHP data server. 

Playing by ear is a top-down defense/reaction-time game built (based on tooling references below) most likely in Clickteam Fusion 2.5, developed as part of a research study. 
The player defends a central point (a "grandma") from waves of enemies spawning from marked zones, while a fog-of-war (FOW) increases difficulty as the player progresses. 

## 1. Game flow

The game is organized into levels and stages. A session save records the
current level and stage, while trial data records the individual targets
completed during that save.

The game supports two gameplay modes:

- **Mode 1:** the original enemy/audio-selection flow.
- **Mode 2:** enemy/friend targets using the `Friend_OR_Enemy` value.

----------------------------------------------------------------------------------

2. Tech Stack
|Layer     |                      Technology|<br>
|--------------------------|-------------------|<br>
|Game engine           |          Clickteam Fusion 2.5 |<br>
|Client platform         |        Desktop build + HTML5/web export|<br>
|Backend/server          |        PHP + MySQL|<br>
|Hosting                 |        Railway|<br>
|Source control          |        GitHub|<br>
|Distribution             |       itch.io|<br>
|Study/participant recruitment  | Prolific|<br>

---------------------------------------------------------------------------------

3. Core Gameplay Systems (by feature area)

3.1 Central Defense Point
"Grandma" character - a rotating turret with animation and reticle tracking that follows the mouse/player pointer.

3.2 Spawners / Enemy System
Spawnzones and Score/Speed Rings — spawn points in each corner. <br>
Randomized enemy spawning code (RRand (1,4) -spawn location is determined by a random number generator. <br>
Invisible rings on the map that determines how many points are gained when an enemy is shot - the closer the more points. <br>

Known historical bugs (should confirm current status):<br>
Enemy not respawning on player hit bug. Player can hit SPACE bar to respawn a bugged enemy<br>
Audio not playing on player hit bug. Player can hit SPACE bar to respawn a bugged enemy<br>
Watchdog function that attempts to fix spanning if player has not given any input for longer than a trial lasts.<br>

3.3 Combat / Player Input
Basic projectile raytracing- Cookie Launcher 
=In mode 1, left click fires. Mode 2 implements a right click. 

3.4 Health / Resource / Scoring
Basic Healthbar
Decreases at a rate of -1/second. 
Firing a shot costs health (-10) and health is regained on hit (+15) 


----------------------------------------------------------------------------------

3.5 Fog of War (FOW)<br>
dynamic FOW built → FOW visibility decreases in stages 2 & 3<br>
--> Each hit decreases visibility by 1/10<br>


3.6 Streak / Reward System<br>
1 hit yields from (distance (1-6) * level (1-5)) points based on distance. <br>
1 hit of an enemy covered by the Fog Of War yields (distance (1-6) * current combo * level (1-5)) points<br>

----------------------------------------------------------------------------------

3.7 Audio

L1:|F0&Vowel|<br>

TL-275x35 B/P i<br>
TR- 275x35 B/P e<br>
BL- 165x35 B/P i<br>
BR- 165x35 B/P e<br>

L2:<br>
TL-275x(rrand(30,40)) B/P i<br>
TR- 275x(rrand(30,40)) B/P e<br>
BL- 165x(rrand(30,40)) B/P i<br>
BR- 165x(rrand(30,40)) B/P e<br>

L3:<br>

TL-275x (rrand(20,50)) B/P i<br>
TR- 275x(rrand(20,50)) B/P e<br>
BL- 165x(rrand(20,50)) B/P i<br>
BR- 165x(rrand(20,50)) B/P e<br>

L4:<br>

TL-260x (rrand(20,50)) B/P i<br>
TR- 260x(rrand(20,50)) B/P e<br>
BL- 180x(rrand(20,50)) B/P i<br>
BR- 180x(rrand(20,50)) B/P e<br>

L5:<br>

TL-245x (rrand(20,50)) B/P i<br>
TR- 245x(rrand(20,50)) B/P e<br>
BL- 195x(rrand(20,50)) B/P i<br>
BR- 195x(rrand(20,50)) B/P e<br>

MODE 2:<br>
High F0 = Friend<br>
Low F0 = Enemy<br>

L1:<br>
                    
275x (rrand(35,40)) B/P i<br>
275x(rrand(35,40)) B/P e<br>
165x(rrand(35,40)) D/T i<br>
165x(rrand(35,40)) D/T e<br>

L2:<br>

275x (rrand(30,40)) B/P i<br>
275x(rrand(30,40)) B/P e<br>
165x(rrand(30,40)) D/T i<br>
165x(rrand(30,40)) D/T e <br>

L3<br>
TL-275x (rrand(20,50)) B/P i <br>
TR- 275x(rrand(20,50)) B/P e<br>
BL- 165x(rrand(20,50)) D/T i<br>
BR- 165x(rrand(20,50)) D/T e<br>

L4<br>

TL-275x (rrand(20,50)) B/P i<br>
TR- 275x(rrand(20,50)) B/P e<br>
BL- 165x(rrand(20,50)) D/T i<br>
BR- 165x(rrand(20,50)) D/T e<br>

L5<br>

TL-275x (rrand(20,50)) B/P i<br>
TR- 275x(rrand(20,50)) B/P e<br>
BL- 165x(rrand(20,50)) D/T i<br>
BR- 165x(rrand(20,50)) D/T e<br>

----------------------------------------------------------------------------------

Stage values are assigned by the game and sent in two different places:

- The session stage is sent as the URL parameter `stage=...`.
- The trial stage is stored in the trial array at Y=4 and sent inside each
  TrialData row.

These values are independent. A session can have `stage=1` while a trial row
still contains stage `0` if the array value was not assigned.

----------------------------------------------------------------------------------



## 2. Trial Text Array

The Text Array is the source of completed trial rows. **Base 1 Index must be
disabled** so coordinates start at zero and match the expressions used by the
game:

| Array Y | Field | Meaning |
|---:|---|---|
| 0 | stimulus | Stimulus text, such as `165x35 B/P i` |
| 1 | reaction time | Response time in seconds |
| 2 | hit | Hit/miss code, normally 1/0 |
| 3 | enemy number | Target or enemy identifier |
| 4 | stage | Current stage |
| 5 | target type | `Friend_OR_Enemy`, or another target classification |
| 6 | audio selection | Numeric audio/stimulus selection identifier |

The game reads cells with expressions such as:

```text
StrAtXY("Array", LogIndex, 0)
```

`LogIndex` identifies the current trial row. It must be incremented only after
the row has been completed and appended to `TrialData`.

## 3. TrialData format

The current seven-field format is:

```text
stimulus~reaction_time~hit~enemy_number~stage~target_type~audio_selection|
```

Clickteam append expression:

```text
TrialData
+ StrAtXY("Array", LogIndex, 0) + "~"
+ StrAtXY("Array", LogIndex, 1) + "~"
+ StrAtXY("Array", LogIndex, 2) + "~"
+ StrAtXY("Array", LogIndex, 3) + "~"
+ StrAtXY("Array", LogIndex, 4) + "~"
+ StrAtXY("Array", LogIndex, 5) + "~"
+ StrAtXY("Array", LogIndex, 6)
+ "|"
```

Every field needs a delimiter. In particular, a delimiter must occur between
Y=4 and Y=5.

The normal order is:

1. Spawn or select the target.
2. Write stimulus, target type, audio selection, and target number.
3. On hit or miss, write reaction time and hit/miss.
4. Write the current stage.
5. Append the completed row to `TrialData`.
6. Increment `LogIndex`.

## 4. Mode 1 stimulus system

Mode 1 uses the selected audio number to choose a stimulus. Existing events
follow this pattern:

```text
Audio selection = X
Friend_OR_Enemy = 0
→ Write the matching stimulus string to (LogIndex, 0)
→ Write Friend_OR_Enemy to (LogIndex, 5)
```

The stimulus text must match the audio selection mapping. The audio number is
also written to Y=6 so a missing stimulus can be reconstructed later.

## 5. Mode 2 target system

Mode 2 uses the same stimulus-writing pattern but supports different target
types. `Friend_OR_Enemy` is written to Y=5:

```text
0 = enemy
1 = friend
```

At target selection or spawn, mode 2 should write:

```text
Write selected stimulus to (LogIndex, 0)
Write Friend_OR_Enemy to (LogIndex, 5)
Write AudioSelection to (LogIndex, 6)
```

Mode 2 testing showed that reaction time, hit/miss, target type, stage, and
audio selection can be saved even when stimulus is blank. That pattern means
the append and server parser are working while the Y=0 write is missing,
late, indexed differently, or overwritten.

## 6. Audio selection and playback

Spawners choose an audio-selection range:

```text
Spawner 1 → RRandom(1, 4)
Spawner 2 → RRandom(5, 8)
Spawner 3 → RRandom(9, 12)
Spawner 4 → RRandom(13, 16)
```

The retry path must reuse the existing `AudioSelection`; it must not
randomize a new value because that could make the played audio disagree with
the saved stimulus.

Ground zones can be used as a one-shot retry trigger. A retry flag or
per-target value prevents the zone from replaying audio every frame. A
`No sample is playing` condition may be used after a short delay, but it
cannot prove that the player heard the sound. `Resume all sounds` only resumes
paused audio and does not restart a sample that never began.

## 7. Target spawning

Normal spawning is triggered by the `Enemy Die` event:

```text
Enemy Die
→ Set SpawnChoice to RRandom(1, 4)
→ Create the next enemy at the selected spawner
→ Set SpawnChoice to 0
→ Add 1 to Enemy_Number_
→ Write Enemy_Number_ to (LogIndex, 3)
```

The enemy counter and `LogIndex` are different counters. Enemy numbers can
skip because some targets may not be logged, while `LogIndex` counts retained
trial rows.

If several enemies can exist simultaneously, a single global current-row
value can be overwritten by a later spawn. The robust design is to store the
trial row on each enemy object as an alterable value. If only one target can
exist at a time, a global current row is sufficient.

## 8. Spawn watchdog

A global watchdog can protect against a missed `Enemy Die` spawn without
changing every frame-level spawn event:

```text
Number of Enemy objects = 0
SpawnWatchdog timer > normal maximum delay
SpawnWatchdogUsed = 0
```

Actions:

```text
Set SpawnWatchdogUsed to 1
Set SpawnChoice to RRandom(1, 4)
Create an enemy using the existing four-spawner branches
Reset the watchdog timer
```

The timeout should be longer than the normal spawn delay so it does not create
an extra target during ordinary timing variation. The watchdog catches an
empty field; it does not detect an enemy that exists but is stuck.

## 9. Trial-data safeguards

Safeguards belong immediately above the existing TrialData append event and
must use the same hit/miss completion condition.

For each field, compare the array cell to an empty string:

```text
StrAtXY("Array", LogIndex, Y) = ""
```

If empty, repeat the existing write action for that field. The intended
fallbacks are:

- Y=0: rewrite the selected stimulus.
- Y=1: rewrite the existing reaction-time calculation. Also repair zero if
  zero is invalid, because `0` is not an empty string.
- Y=2: rewrite the existing hit/miss value; do not treat `0` as missing.
- Y=3: rewrite the existing target/enemy number when missing or zero, using
  the real target counter rather than `LogIndex`.
- Y=4: rewrite the current stage. Stage `0` is valid.
- Y=5: rewrite `Friend_OR_Enemy`.
- Y=6: rewrite `AudioSelection`.

The append must run after these safeguards. `LogIndex` must not be incremented
by a safeguard event.

## 10. Save request

The game builds a GET request to:

```text
https://bpgranny-production.up.railway.app/save_session.php
```

Required values include:

```text
gameid
playername
score
code
```

The security hash is:

```text
MD5(gameid + playername + score + secret_key)
```

The hash must be recalculated whenever the score changes. Build `SaveURL` from
the base URL before every save so old parameters do not accumulate. Assign the
current stage before appending:

```text
&stage=Str$(Value("Stage 1-3"))
```

The URL stage and the trial-array stage are separate values and should be
assigned from the same current stage value.

`Get URL` is asynchronous. A `Next Frame` action immediately after `Get URL`
does not wait for completion. Keep one active save path for each transition.

## 11. Duplicate saves

Multiple Clickteam GET actions create multiple database sessions. Identical
payloads received within the configured deduplication window are fingerprinted
and return the original session instead of inserting another one.

This protects the database but does not replace removing duplicate Global Event
or frame-level `Get URL` actions.

## 12. Server database

`game_sessions` stores one row per save:

- game ID and player ID
- level and stage
- score and mistakes
- total shots fired
- play time
- save timestamp

`game_trials` stores one row per retained trial:

- session ID and player ID
- trial index
- stimulus
- reaction time
- hit/miss
- enemy number
- stage
- target type
- audio selection

Trials with reaction time `<= 0` are ignored by the server.

The server automatically creates and migrates required columns, including
`audio_selection`. The obsolete `click_type` column was removed.

## 13. Exports

The protected combined export is:

```text
https://bpgranny-production.up.railway.app/export.php?password=YOUR_EXPORT_PASSWORD
```

The sessions-only export is:

```text
https://bpgranny-production.up.railway.app/export.php?table=game_sessions&password=YOUR_EXPORT_PASSWORD
```

`EXPORT_PASSWORD` is configured in Railway Variables. Do not commit or
publicly share a completed export URL because it contains the password.

The sessions-only export contains one row per `game_sessions` record. The
combined export contains session fields followed by trial fields, including
`target_type` and `audio_selection`.
