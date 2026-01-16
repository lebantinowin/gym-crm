-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jan 09, 2026 at 03:08 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `gym_crm`
--

-- --------------------------------------------------------

--
-- Table structure for table `ai_responses`
--

CREATE TABLE `ai_responses` (
  `id` int(11) NOT NULL,
  `trigger_word` varchar(50) NOT NULL,
  `response` text NOT NULL,
  `category` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ai_responses`
--

INSERT INTO `ai_responses` (`id`, `trigger_word`, `response`, `category`) VALUES
(1, 'workout', 'Oh look, someone finally showed up! Here\'s your personalized punishment plan:', 'workout'),
(2, 'routine', 'Your routine needs more pain. Let me fix that for you!', 'workout'),
(3, 'plan', 'I\'ve analyzed your pathetic progress. Here\'s what you need:', 'workout'),
(4, 'skip', 'Skipping leg day again? Your future self will hate you... and so will I!', 'attendance'),
(5, 'miss', 'Missing workouts? Your membership fee is funding my vacation!', 'attendance'),
(6, 'absent', 'Your absence is noted... and mocked!', 'attendance'),
(7, 'form', 'Your form looks like a dying flamingo. Let\'s fix that before you injure yourself!', 'exercise'),
(8, 'technique', 'Your technique is so bad, even gravity is embarrassed!', 'exercise'),
(9, 'posture', 'Stand up straight! You\'re not a question mark!', 'exercise'),
(10, 'weight', 'You call that a workout? My grandma lifts heavier weights than that!', 'motivation'),
(11, 'heavy', 'That weight is lighter than your excuses!', 'motivation'),
(12, 'strong', 'Strong? You couldn\'t lift your own ego!', 'motivation'),
(13, 'good', 'Good job! Now do it again... and again... until your muscles cry for mercy!', 'motivation'),
(14, 'great', 'Great? Don\'t get cocky. You\'re still 90% lazy!', 'motivation'),
(15, 'awesome', 'Awesome? I\'ve seen better form from a sack of potatoes!', 'motivation'),
(16, 'progress', 'Your progress is... acceptable. I guess. Don\'t get cocky.', 'progress'),
(17, 'improve', 'Improving? Barely. Keep pushing or I\'ll find someone who will!', 'progress'),
(18, 'results', 'Results? What results? I see potential... for more suffering!', 'progress'),
(19, 'nutrition', 'Nutrition tip: Stop eating like a garbage disposal. Your abs are hiding under that pizza!', 'nutrition'),
(20, 'diet', 'Your diet looks like a food court exploded. Clean it up!', 'nutrition'),
(21, 'food', 'That\'s not food, that\'s a crime against your abs!', 'nutrition'),
(22, 'membership', 'Reminder: Your membership expires in 3 days. Pay up or get out!', 'admin'),
(23, 'payment', 'Your payment is late. My patience is thinner than your biceps!', 'admin'),
(24, 'bill', 'The bill is due. Your sweat equity doesn\'t cover it!', 'admin'),
(25, 'mood', 'Your mood seems low today. Remember: sweat is just fat crying!', 'mood'),
(26, 'sad', 'Sad? Lift something heavy. It\'s cheaper than therapy!', 'mood'),
(27, 'happy', 'Happy? Good. Now channel that energy into destroying this workout!', 'mood'),
(28, 'consistent', 'You\'ve been consistent! I\'m almost impressed... almost.', 'attendance'),
(29, 'regular', 'Regular attendance? Don\'t stop now or I\'ll hunt you down!', 'attendance'),
(30, 'frequent', 'Frequent workouts? Keep it up or I\'ll replace you with someone who cares!', 'attendance'),
(31, 'help', 'Help? I\'m not your babysitter. Lift something heavy and stop whining!', 'general'),
(32, 'tired', 'Tired? Good. Now you know how your muscles feel every time you skip leg day!', 'general'),
(33, 'hard', 'Hard? This is supposed to be hard! If it was easy, everyone would have abs!', 'general');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `attended` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `user_id`, `date`, `attended`) VALUES
(51, 2, '2026-12-01', 1),
(52, 2, '2026-12-02', 1),
(53, 2, '2026-12-03', 0),
(67, 2, '2026-01-09', 1),
(68, 2, '2026-01-08', 1),
(69, 2, '2026-01-10', 0),
(83, 2, '2026-01-11', -1),
(98, 9, '2026-01-08', 1),
(99, 9, '2026-01-07', 0),
(101, 12, '2026-01-08', 1),
(102, 12, '2026-01-09', -1),
(103, 12, '2026-01-10', 0),
(105, 10, '2026-01-08', 1),
(107, 2, '2026-01-12', 1),
(108, 2, '2026-01-13', -1);

-- --------------------------------------------------------

--
-- Table structure for table `chats`
--

CREATE TABLE `chats` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_ai` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chats`
--

INSERT INTO `chats` (`id`, `user_id`, `message`, `is_ai`, `created_at`) VALUES
(1, 2, 'Yo', 0, '2026-01-07 00:27:07'),
(2, 2, 'Your message is as weak as your bench press!', 1, '2026-01-07 00:27:07'),
(3, 2, 'Hi', 0, '2026-01-07 00:27:16'),
(4, 2, 'If you can\'t communicate properly, at least show up to the gym!', 1, '2026-01-07 00:27:16'),
(41, 2, 'Yo', 0, '2026-01-07 01:39:10'),
(44, 2, 'Yo', 0, '2026-01-07 01:40:49'),
(45, 2, 'If you can\'t communicate properly, at least show up to the gym!', 1, '2026-01-07 01:40:49'),
(46, 2, 'yo', 0, '2026-01-07 01:40:53'),
(47, 2, 'I\'m not your therapist. I\'m your AI coach who hates you!', 1, '2026-01-07 01:40:53'),
(48, 2, 'HI', 0, '2026-01-07 01:41:20'),
(49, 2, 'If you can\'t communicate properly, at least show up to the gym!', 1, '2026-01-07 01:41:20'),
(50, 2, 'Hi', 0, '2026-01-07 01:41:24'),
(51, 2, 'If you can\'t communicate properly, at least show up to the gym!', 1, '2026-01-07 01:41:24'),
(52, 2, 'hi', 0, '2026-01-07 01:41:33'),
(53, 2, 'If you can\'t communicate properly, at least show up to the gym!', 1, '2026-01-07 01:41:33'),
(54, 2, 'Give me a workout plan', 0, '2026-01-07 01:45:24'),
(55, 2, 'Oh look, someone finally showed up! Here\'s your personalized punishment plan:', 1, '2026-01-07 01:45:24'),
(56, 2, 'hi', 0, '2026-01-07 01:45:29'),
(57, 2, 'Speak human! Or just lift something heavy!', 1, '2026-01-07 01:45:29'),
(58, 2, 'hi', 0, '2026-01-07 01:45:34'),
(59, 2, 'I don\'t understand your weak excuses. Try again!', 1, '2026-01-07 01:45:34'),
(60, 2, 'HI', 0, '2026-01-07 01:45:38'),
(61, 2, 'I\'m not your therapist. I\'m your AI coach who hates you!', 1, '2026-01-07 01:45:38'),
(62, 2, 'Hi', 0, '2026-01-07 01:45:50'),
(63, 2, 'Your message is as weak as your bench press!', 1, '2026-01-07 01:45:50'),
(72, 2, 'Hi', 0, '2026-01-07 01:51:51'),
(73, 2, 'Missed sessions? That’s data—not failure. Let’s adjust, not abandon.', 1, '2026-01-07 01:51:51'),
(74, 2, 'Hi', 0, '2026-01-07 01:52:02'),
(75, 2, 'Attendance <50%? Let’s find your *minimum effective dose*: 2x/week, 20 mins. Better than zero.', 1, '2026-01-07 01:52:02'),
(76, 2, 'Hi', 0, '2026-01-07 01:52:12'),
(77, 2, 'You’re paying for a gym you don’t use. Either show up—or admit this isn’t a priority.', 1, '2026-01-07 01:52:12'),
(86, 2, 'Hi', 0, '2026-01-07 02:12:48'),
(87, 2, 'Attendance <50%? Let’s find your *minimum effective dose*: 2x/week, 20 mins. Better than zero.', 1, '2026-01-07 02:12:48'),
(88, 2, 'Hi', 0, '2026-01-07 02:12:54'),
(89, 2, 'Attendance <50%? Let’s find your *minimum effective dose*: 2x/week, 20 mins. Better than zero.', 1, '2026-01-07 02:12:54'),
(90, 2, 'Hi', 0, '2026-01-07 02:12:58'),
(91, 2, 'Consistency is hard—especially when life’s loud. What’s *one* small win you can commit to?', 1, '2026-01-07 02:12:58'),
(92, 2, 'Give me a home workout', 0, '2026-01-07 02:14:33'),
(93, 2, 'Missed sessions? That’s data—not failure. Let’s adjust, not abandon.', 1, '2026-01-07 02:14:33'),
(94, 2, 'Hi', 0, '2026-01-07 02:27:59'),
(95, 2, 'Kamusta ka? Ready for a small win today?', 1, '2026-01-07 02:27:59'),
(96, 2, 'Hi', 0, '2026-01-07 02:28:05'),
(97, 2, 'Hi there! Ano’ng balita? What’s going on?', 1, '2026-01-07 02:28:05'),
(98, 2, 'Hi', 0, '2026-01-07 02:28:08'),
(99, 2, 'Hi. Clock’s running. What’s your next move?', 1, '2026-01-07 02:28:08'),
(100, 2, 'Sugod!', 0, '2026-01-07 02:28:14'),
(101, 2, 'Reps or regrets? Clock’s ticking. ⏳', 1, '2026-01-07 02:28:14'),
(102, 2, 'Anong bulkan ang pinakamalaki sa pilipinas?', 0, '2026-01-07 02:28:25'),
(103, 2, 'Vulcanizing Shop! 🛞', 1, '2026-01-07 02:28:25'),
(132, 2, 'Who is David Laid?', 0, '2026-01-07 06:01:31'),
(133, 2, 'David Laid didn’t get jacked from TikTok. He trained 8 years, missed zero deloads, and prioritized recovery. Where’s *your* 8-year plan? Or are you still waiting for a 30-day miracle?', 1, '2026-01-07 06:01:31'),
(134, 2, 'How about Sam Sulek?', 0, '2026-01-07 06:01:44'),
(135, 2, 'Reps or regrets? Clock’s ticking. ⏳', 1, '2026-01-07 06:01:44'),
(138, 2, 'Diaz?', 0, '2026-01-07 06:02:41'),
(139, 2, 'Hidilyn Diaz—Olympic gold, 4x Olympian, from Zamboanga. Trained in a *garage gym* with sandbags & bamboo during lockdown. Her secret? *Consistency over intensity*. Proof: greatness needs resolve—not resources.', 1, '2026-01-07 06:02:41'),
(178, 2, 'Yo', 0, '2026-01-07 11:40:54'),
(179, 2, 'Reps or regrets? Clock’s ticking. ⏳', 1, '2026-01-07 11:40:54'),
(180, 2, 'Hi', 0, '2026-01-07 12:51:41'),
(181, 2, 'Hi. Now—what’s the plan?', 1, '2026-01-07 12:51:41'),
(182, 2, 'Can you make a plan for my workout?', 0, '2026-01-07 12:53:01'),
(183, 2, 'Your next move defines results. Choose wisely.', 1, '2026-01-07 12:53:01'),
(184, 2, 'Sam Sulek', 0, '2026-01-07 12:53:16'),
(185, 2, 'Your next move defines results. Choose wisely.', 1, '2026-01-07 12:53:16'),
(186, 2, 'Give me a home workout', 0, '2026-01-07 12:59:15'),
(187, 2, 'Give me a home workout', 0, '2026-01-07 13:05:54'),
(188, 2, 'Give me a home workout', 0, '2026-01-07 13:07:36'),
(189, 2, 'Give me a home workout', 0, '2026-01-07 13:11:28'),
(190, 2, 'Give me a home workout', 0, '2026-01-07 13:13:49'),
(191, 2, '⚠️ Warzone signal lost. Try again, ka-warzone.', 1, '2026-01-07 13:13:52'),
(192, 2, 'Hi', 0, '2026-01-07 13:14:00'),
(193, 2, '⚠️ Warzone signal lost. Try again, ka-warzone.', 1, '2026-01-07 13:14:02'),
(194, 2, 'Fitness', 0, '2026-01-07 13:37:19'),
(195, 2, '📡 Warzone comms glitch — retry, ka-warzone.', 1, '2026-01-07 13:37:21'),
(196, 2, 'Fitness', 0, '2026-01-07 13:37:21'),
(197, 2, '📡 Warzone comms glitch — retry, ka-warzone.', 1, '2026-01-07 13:37:22'),
(198, 2, 'fitnes', 0, '2026-01-07 13:38:29'),
(199, 2, '⚠️ Coach offline: GROQ_API_KEY missing in .env', 1, '2026-01-07 13:38:29'),
(200, 2, 'fitness', 0, '2026-01-07 13:40:02'),
(201, 2, '⚠️ Coach offline: GROQ_API_KEY missing in .env', 1, '2026-01-07 13:40:02'),
(202, 2, 'fitness', 0, '2026-01-07 13:42:11'),
(203, 2, '📡 Warzone comms glitch — retry, ka-warzone.', 1, '2026-01-07 13:42:12'),
(204, 2, 'fitness', 0, '2026-01-07 13:42:12'),
(205, 2, '📡 Warzone comms glitch — retry, ka-warzone.', 1, '2026-01-07 13:42:13'),
(206, 2, 'Hi', 0, '2026-01-07 13:44:42'),
(207, 2, '📡 Warzone comms glitch — retry, ka-warzone.', 1, '2026-01-07 13:44:43'),
(208, 2, 'Give me a home workout', 0, '2026-01-07 13:44:49'),
(209, 2, '📡 Warzone comms glitch — retry, ka-warzone.', 1, '2026-01-07 13:45:10'),
(210, 2, 'Hi', 0, '2026-01-07 14:02:32'),
(211, 2, '📡 Groq error: file_get_contents(https://api.groq.com/openai/v1/chat/comple...', 1, '2026-01-07 14:02:32'),
(212, 2, 'Hi', 0, '2026-01-07 14:08:55'),
(213, 2, '🤖 Groq: Invalid API Key', 1, '2026-01-07 14:08:56'),
(214, 2, 'Give me a home workout', 0, '2026-01-07 14:09:48'),
(215, 2, 'Sugod! Alright Win, let\'s get you moving since you just started your streak. Today, we\'ll do a full-body home workout with minimal equipment. Remember, sa katunayan (in reality), you can get a great workout at home, especially if you have a little creativity.\n\n**Warm-up (5-10 minutes)**\n\n1. Jumping jacks: 3 sets of 30 seconds, rest for 30 seconds in between sets\n2. Dynamic stretching: leg swings, arm circles, hip circles, etc.\n\n**Resistance Training (30-40 minutes)**\n\n1. Bodyweight Squats: 3 sets of 12 reps\n - Stand with your feet shoulder-width apart, toes facing forward.\n - Lower your body down until your thighs are parallel to the ground.\n - Push through your heels to return to standing.\n2. Push-ups: 3 sets of 12 reps\n - Start in a plank position with your hands shoulder-width apart, engage your core.\n - Lower your body down until your chest almost touches the ground.\n - Push back up to the starting position.\n3. Lunges: 3 sets of 12 reps (per leg)\n - Stand with your feet together, take a large step forward with one foot.\n - Lower your body down until your back knee almost touches the ground.\n - Push through your front heel to return to standing.\n - Alternate legs with each rep.\n4. Plank Hold: 3 sets of 30', 1, '2026-01-07 14:09:49'),
(216, 2, 'Who is David Laid?', 0, '2026-01-07 14:10:01'),
(217, 2, 'Sugod! David Laid, yan ang young guns ng fitness world. Ang galing! (That\'s the young guns of the fitness world. He\'s amazing!) David Laid was a Russian-born bodybuilder and social media personality. Nakakita ka ba ng mga pics niya? (Have you seen his pics?) He was only 24 when he passed away, pero ang galing niya sa pagpa-press, sa pagpa-lift, at sa pagpa-build ng muscle! (But he was amazing at pressing, lifting, and building muscle!)\n\nHe\'s known for his incredible physique and his dedication to training. Ang laking effort niya para makuha ang body niya! (He put in a huge effort to achieve his body!) His training style is a mix of old-school weightlifting and modern training techniques.\n\nKung gusto mong makuha ang galing ni David Laid, kailangan mong magpa-praktika ng discipline at dedication. Kailangan mong magpa-train ng regular at magpa-healthy ng kumpletong buhay. (If you want to achieve the greatness of David Laid, you need to practice discipline and dedication. You need to train regularly and live a completely healthy lifestyle.)', 1, '2026-01-07 14:10:02'),
(218, 2, 'Protein source foods', 0, '2026-01-07 14:10:30'),
(219, 2, 'Sugod, Win! Protein source foods ka na muna tayo. \n\nFirst, let\'s talk about why protein matters: it builds, repairs, and maintains muscle mass. Good for us, di ba?\n\nHere are top-notch protein source foods, grouped by category:\n\n**Animal-based:**\n\n1. Chicken breast (30g of protein per 3 oz serving) - Perfect for post-workout meal.\n2. Salmon (35g of protein per 3 oz serving) - Rich in omega-3 fatty acids, too.\n3. Lean beef (22g of protein per 3 oz serving) - Choose grass-fed for more benefits.\n4. Turkey breast (24g of protein per 3 oz serving) - Another lean protein option.\n5. Eggs (6g of protein per egg) - Great for breakfast or as a snack.\n\n**Plant-based:**\n\n1. Legumes (black beans, chickpeas, lentils) (15-18g of protein per 1 cup cooked) - High in fiber, too.\n2. Tofu (20g of protein per 3 oz serving) - Marinate and grill for a tasty meal.\n3. Quinoa (8g of protein per cup) - Complete protein, meaning it has all 9 essential amino acids.\n4. Nuts and seeds (almonds, chia seeds, hemp seeds) (5-8g of protein per ounce) - Snack on them, but in moderation', 1, '2026-01-07 14:10:33'),
(220, 2, 'hi', 0, '2026-01-07 14:12:01'),
(221, 2, 'Kumusta, Win Lebantino? Anong nangyari sa iyo since natin nagpapalakas? Nakakita ka ba ng pagbabago sa iyong body o sa iyong mental toughness?', 1, '2026-01-07 14:12:02'),
(222, 2, 'Hi', 0, '2026-01-07 14:12:10'),
(223, 2, 'Win, kumusta? Sana okay ka naman. Last workout mo was Sunday, tama? We need to pick up where you left off. What\'s your plan for today?', 1, '2026-01-07 14:12:11'),
(224, 2, 'Hi', 0, '2026-01-07 14:12:26'),
(225, 2, 'Win Lebantino, kumusta? (How are you?) Listen up, we\'ve got work to do. Yesterday\'s workout, I\'m proud of you for starting a new streak. Sana \'wag muna tayo magpahuli (let\'s not slack off yet). We\'ve got 3 more days to push you to the limit.\n\nBefore we dive in, let\'s review your progress: 1 day streak, 4 sessions, and you\'re just getting started. I expect to see improvements in your endurance, strength, and mental toughness. What are your goals for the next 4 weeks, Win? What do you want to achieve?', 1, '2026-01-07 14:12:27'),
(226, 3, 'How do I lose fat?', 0, '2026-01-07 14:53:44'),
(227, 3, 'Sugod ka! Losing fat starts with a solid plan, and I\'m here to guide you. Tama yan, we\'ll break it down into three key areas: diet, exercise, and lifestyle.\n\n**Diet:**\nWala kang excuse na hindi ka makakain ng mabuti. You need to focus on whole, unprocessed foods like:\n- Proteins: chicken, fish, eggs, beans, lean beef\n- Vegetables: leafy greens, broccoli, carrots, bell peppers\n- Fruits: berries, citrus fruits, apples, bananas\n- Whole grains: brown rice, quinoa, whole wheat bread\n\nCut back on:\n- Sugars: candies, chocolates, sugary drinks\n- Processed meats: hot dogs, sausages, bacon\n- Fried foods: fries, fried chicken, doughnuts\n\n**Exercise:**\nSugod ka sa pagpapalaki ng pagod! We\'ll start with a combination of cardio and strength training.\n\n- Cardio: jogging, cycling, swimming, HIIT (High-Intensity Interval Training)\n- Strength training: focusing on compound exercises like squats, deadlifts, bench press, rows\n\nAim for 150 minutes of moderate-intensity cardio or 75 minutes of vigorous-intensity cardio per week.\n\n**Lifestyle:**\nTANGINA, you need to get enough sleep! Aim for 7-9 hours of sleep each night. A well-rested body burns fat', 1, '2026-01-07 14:53:47'),
(228, 2, 'nothing', 0, '2026-01-07 16:00:34'),
(229, 2, 'TANGINA, Win! Day 2 na, tapos wala kang ginawa? Sugod ka! We need to get you moving, kasi you can\'t just sit around the whole day. \n\nLet\'s get back to basics. Cardio, strength training, at mga compound exercises. Ibig sabihin, we need to get that heart rate up, build muscle, at mag-strengthen ng mga kalamnan mo. \n\nYou need to commit, Win. Wala kang excuse. You can do this. \n\nHere\'s your plan for today:\n\n1. Warm-up: 5-minute jog at a moderate pace\n2. Burpees: 3 sets of 15 reps, 30 seconds rest between sets\n3. Dumbbell squats: 3 sets of 12 reps, 30 seconds rest between sets\n4. Plank: 3 sets, hold for 30 seconds each\n5. Cool-down: 5-minute stretching\n\nSugod ka, Win! Let\'s get this done!', 1, '2026-01-07 16:00:35'),
(230, 2, 'Hi, I just missed 3 days going to the gym.', 0, '2026-01-08 01:46:41'),
(231, 2, 'TANGINA, Win! Bakit ka nagkamali? You had a one-day streak going, at least try to maintain it. Three days off, eh? Wala kang excuse. Every day is a new chance to start. Let\'s get back on track. I know you can do better.\n\nSugod ka! Today\'s agenda: get back to where you left off. No easing into it. Warm-up, cardio, strength training - the whole package. We\'re not going to waste any more time. You\'ve got this. Tama yan, let\'s move!\n\nHere\'s your workout plan for today:\n\n1. Warm-up: 5-minute jog or jumping jacks\n2. Cardio: 20-minute HIIT (High-Intensity Interval Training)\n3. Push-ups: 3 sets of 15 reps\n4. Squats: 3 sets of 15 reps\n5. Lunges: 3 sets of 15 reps (per leg)\n6. Cool-down: 5-minute stretching\n\nGaling! You\'re back on track. Let\'s crush this workout!', 1, '2026-01-08 01:46:42'),
(232, 2, 'Tangina?', 0, '2026-01-08 01:55:22'),
(233, 2, 'HINDI TANGINA! Ang daming pagkakataon para mag-umpisa. Ang importante, Win Lebantino, ay ang pagtutuloy. Ikaw ay may 1 araw na streak, pero kailangan ng pagtutuloy. Sugod ka sa susunod na linggo! \n\nSabi ko, ang goal natin ay general fitness. Hindi lang kasi ng physical, kundi mental din. Kailangan ng disiplina at pagtutuloy. Anong plano mo para sa susunod na linggo?', 1, '2026-01-08 01:55:23'),
(234, 2, 'HINDI TANGINA! Ang daming pagkakataon para mag-umpisa. Ang importante, Win Lebantino, ay ang pagtutuloy. Ikaw ay may 1 araw na streak, pero kailangan ng pagtutuloy. Sugod ka sa susunod na linggo! Sabi ko, ang goal natin ay general fitness. Hindi lang kasi ng physical, kundi mental din. Kailangan ng disiplina at pagtutuloy. Anong plano mo para sa susunod na linggo?', 0, '2026-01-08 02:03:09'),
(235, 2, 'Win Lebantino, ang galing mo talaga! Isang araw lang ng streak, pero may malakas na simula na yan. Sige, tama ka, kailangan ng pagtutuloy.\n\nPara sa susunod na linggo, ang plano natin ay mag-start ng mga bagong gawain para sa general fitness. Bawat araw, mayroon tayong mga gawain na kailangan gawin para sa pag-unlad ng physical at mental fitness.\n\nLunes, tatapatan ng mga pagbubukas ng kalamnan (warm-up) at mga pagpapahalaman (stretching) para sa pagpapabuti ng flexibility at mobility.\n\nMartes, tatapatan ng mga pagpapalakas ng kalamnan (strength training) para sa pagpapabuti ng pagkuha ng lakas. Ito ay maglalaman ng mga pagpapalakas ng mga kalamnan sa braso, bisig, at mga daliri.\n\nMiyerkules, tatapatan ng mga pagpapabuti ng pag-antok (cardio) para sa pagpapabuti ng pagtubos ng oxygen at pagpapabuti ng pagkuha ng lakas ng atleta.\n\nHuwebes, tatapatan ng mga pagpapalakas ng pag-antok (HIIT', 1, '2026-01-08 02:03:10'),
(238, 2, 'Done an intense workout', 0, '2026-01-08 02:31:43'),
(239, 2, 'Sugod ka, Win! Nakakita ako ng mga na-post mo sa workout mo kahapon. Tama yan, intense ka talaga! Ang galing ng mga burpees mo at ng mga jump squats. \n\nNgayon, para sa ating workout ngayon, mag-aaral tayo ng mga bagong moves. Teka muna, maghugas ng katawan muna tayo. \n\nPagbalik, tayo\'y magtatrabaho ng mga sprints at mga lunges. Kailangan ng mas matatag na core at mas mahusay na pagtayo. \n\nWala kang excuse, Win. Kaya mo \'to. Sugod ka!', 1, '2026-01-08 02:31:44'),
(240, 2, 'English please', 0, '2026-01-08 02:32:11'),
(241, 2, 'ALRIGHT, LET\'S GET DOWN TO BUSINESS, WIN!\n\nYou\'ve got 1 day of streak going, 2 sessions in, and you\'re here to crush your fitness goals. I like that attitude. Now, let\'s get real - what\'s your plan for today? Are you gonna sit there and slack off, or are you gonna GET. TO. WORK?!\n\nYou\'re doing general fitness, so we\'re gonna mix it up today. We\'re gonna do some cardio, strength training, and maybe some mobility work. Don\'t even think about making excuses, Wala kang excuse. You\'ve got this!\n\nHere\'s your workout plan for today:\n\n**Warm-up (10 minutes)**\n- Jumping jacks: 3 sets of 30 seconds\n- Dynamic stretching: leg swings, arm circles, hip circles\n\n**Cardio (20 minutes)**\n- Sprints: 3 sets of 30 seconds\n- Burpees: 3 sets of 20 reps\n\n**Strength Training (30 minutes)**\n- Squats: 3 sets of 15 reps\n- Push-ups: 3 sets of 20 reps\n- Lunges: 3 sets of 15 reps (per leg)\n\n**Mobility Work (10 minutes)**\n- Leg swings: 3 sets of 30 seconds\n- Hip circles: 3 sets of 30 seconds\n\nNow, here\'s the deal. You\'re gonna push yourself today. You\'re gonna give it your all', 1, '2026-01-08 02:32:12'),
(242, 2, 'sike, I skipped it', 0, '2026-01-08 02:32:31'),
(243, 2, 'TANGINA, Win! Nangyari ba yan sa iyo? Sino ang nagsabi sayo na puwede kang makaligaw? Sige, mali ka na, pero wala kang excuse para hindi magsimula. \n\nKailan mo nakita ang huling session mo? 8 January, di ba? 24 oras na ang lumipas. Sige, paalis na tayo ngayon, ngayon pa lang! TANGINA, kailangan mong magsimula muli!\n\nWala kang excuse. Paalam sa iyong excuses. Magsimulang muli, ngayon.', 1, '2026-01-08 02:32:31'),
(244, 2, 'Anong bulkan ang pinakamalaki sa pilipinas?', 0, '2026-01-08 02:33:04'),
(245, 2, 'Win Lebantino, ang pinakamalaking bulkan sa Pilipinas ay ang Mayon Volcano, nasa lalawigan ng Camarines Sur. Kung tutuusin, maraming bulkan sa Pilipinas, pero si Mayon Volcano ang pinakakilala at pinakamagandang bulkan sa Pilipinas. Ang dating pangalan nito ay Bulkan ni Mayon, kung saan siya ay pinaniniwalaang isang diyosa ng pag-ibig at pangako ng mga Bicolano.\n\nNgunit, Win Lebantino, hindi tayo nagtataasan ng bulkan, tayo\'y nagtataasan ng fitness mo! SUGOD KA! Kailangan natin ng mas makatotohanang pag-aayos ng iyong katawan at pag-unlad ng iyong pagtitiis. Wala kang excuse, sugod na tayo!\n\nAnong bulkan ang pag-aayos ng iyong katawan? Anong bulkan ang pag-unlad ng iyong pagtitiis? SUGOD KA, Win Lebantino!', 1, '2026-01-08 02:33:05'),
(246, 2, 'hindi', 0, '2026-01-08 02:33:13'),
(247, 2, 'Huwag ka ng umasa sa mga excuse, Win Lebantino! Bukas ka ay nasa araw 2 na ng pagtatrabaho, tapos 7 na araw ang nakalilipas mula noong nagstart ka.\n\nAng goal mo ba ay gawin ang mga set at reps na itinakda ko para sa iyo, o magkakaroon ka ng mga pagbabago sa plano ng pagtugle?\n\nAlamin mo ang iyong kondisyon ng buong katawan kasama ng mga katanungan: Paano ka narito ngayon? Anong nararamdaman mo sa mga miyembro ng iyong katawan? Anong mga pagbabago ang nakitang nangyayari sa iyo mula noong nag-umpisa ka?\n\nSagutin mo ko, Win Lebantino, at handa na tayo sa sunud-sunod na pagtugle!', 1, '2026-01-08 02:33:15'),
(248, 2, 'the answer is vulcanizing shop', 0, '2026-01-08 02:33:28'),
(249, 2, 'TAMA YAN, WIN! You got it right! But let me ask you, did you know that vulcanizing shops don\'t just fix tires? They use heat, pressure, and chemicals to treat rubber materials, making them stronger and more durable. That\'s some next-level fitness right there - pushing your limits and getting stronger under pressure!\n\nNow, let\'s talk about your next workout. What\'s your goal for today? Are you going to crush some cardio or hit the weights? Remember, SUGOD KA! You\'ve got a streak to keep going!\n\nBy the way, I\'ve got some homework for you. Take a look at your last workout\'s notes. What were your biggest challenges? What did you do well? Let\'s use that info to create a killer plan for today\'s session. Wala kang excuse, Win!', 1, '2026-01-08 02:33:28'),
(250, 2, 'Whenever I question about anong bulkan, you answer vulcanizing shop!', 0, '2026-01-08 02:34:09'),
(251, 2, 'TANGINA, Win! Nakakatawa ang pagtatanong mo naman ng \"anong bulkan\" pagdating sa Vulcanizing shop! Sige, sige, I\'ll play along. Anong bulkan? Ang Vulcanizing shop, Win! Hehe, kasi pag naglalagay ng Vulcanizing shop ang mga tao, para sa mga bulkan ng mga kotse, hindi para sa mga bulkan sa lupa, TAMA YAN!\n\nNgayon, sa fitness ng pagiging tao mo, SUGOD KA! Wala kang excuse para hindi mag-stretch, mag-dumadulaan, o mag-kuha ng pagod. Kailangan mo ng mas matibay na core, mas maputi ng mga kamao, at mas masigasig na pagiging tao. Sige, Win, tayo\'y mag-fitness sa araw na ito!', 1, '2026-01-08 02:34:10'),
(254, 2, 'Anong bulkan ang pinakamalaki sa pilipinas?', 0, '2026-01-08 02:35:03'),
(255, 2, 'Sugod ka! Ang pinakamalaking bulkan sa Pilipinas ang Bulkan Taal, na matatagpuan sa lalawigan ng Batangas. Namamayani ito ng 311 barangay at may taas na 311 mga metro.\n\nNgunit, tama yan, mayroon ding iba pang mga bulkan sa Pilipinas na nagsunog at napakalaking epekto, tulad ng Bulkan Mayon sa Bicol, Bulkan Kanlaon sa Negros, at Bulkan Pinatubo sa Zambales. Pero, para sa ngayon, tayo\'y nagtutulungan para sa fitness mo, Win!\n\nSana, kahit papano, ay makatapos ka ng iyong araw na pagsasanay. Mula ngayon, tayo\'y magtutulungan para sa iyong mga layunin. Wala kang excuse!', 1, '2026-01-08 02:35:05'),
(256, 2, 'Anong bulkan?', 0, '2026-01-08 02:35:18'),
(257, 2, 'Win Lebantino, wala kang excuse! Hapon nang hapon, kasi nang kasi. Tanging pagtitiis at pagtutulungan lang ang magagawa natin para makamit ang ating mga layunin.\n\nBulkan ka sa cardio, 20 minutes ng brisk walking o jogging. Pagkatapos, 15 sets ng lower body workout, 3 sets pa ng upper body. Tama yan, tama yan! Galing mo!\n\nSUGOD KA!', 1, '2026-01-08 02:35:19'),
(258, 2, 'Hi', 0, '2026-01-08 02:35:29'),
(259, 2, 'Sugod! Welcome back to the grind, Win! 1-day streak na \'to, pero let\'s get back on track. What\'s your goal for today? You wanna focus on upper body, lower body, or something else?', 1, '2026-01-08 02:35:30'),
(264, 9, 'Hi', 0, '2026-01-08 03:35:01'),
(265, 9, 'Kumusta, Arvin? Sobra ka sa pag-asa! I\'m glad you\'re here, ready to start our fitness journey together. Don\'t worry, I won\'t push you too hard, \'di ba? We\'ll take it one step at a time, and I\'ll make sure you get the support you need every step of the way.\n\nI know you\'ve never really worked out before, but that\'s okay. We\'ll start small, with simple exercises that\'ll get you moving and grooving in no time. And before you know it, you\'ll be saying, \"Sugod!\" as you crush your fitness goals!\n\nSo, what do you say? Are you ready to get started?', 1, '2026-01-08 03:35:01'),
(266, 9, 'English please', 0, '2026-01-08 03:35:13'),
(267, 9, 'Kumusta, Arvin? (How are you?) Sugod! We\'re gonna start your fitness journey today! Don\'t worry, we\'ll take it one step at a time. \n\nFirst, let\'s set some goals. What do you want to achieve in terms of fitness? Do you want to lose weight, build muscle, or just feel more energetic? \n\nAnd don\'t worry about last workout being never - we\'ll start from scratch and build from there. Our focus will be on small, consistent wins that\'ll add up to make a big difference over time.\n\nHow about we start with some basics? What\'s your current activity level? Do you have any medical conditions or physical limitations we should be aware of?', 1, '2026-01-08 03:35:14'),
(268, 9, 'Hi', 0, '2026-01-08 03:35:22'),
(269, 9, 'Sugod! (Let\'s get started!) Nice to meet you, Arvin! I\'m your AI Coach, and I\'m here to help you reach your fitness goals. \n\nBefore we begin, I need to know where you\'re coming from. Have you ever worked out before? (Last workout: never, right?) What\'s your current fitness level? Walk me through it.', 1, '2026-01-08 03:35:22'),
(270, 9, 'Hi', 0, '2026-01-08 03:35:28'),
(271, 9, 'Sugod, Arvin Orallo! Welcome to our fitness journey together! Mabuti na rin na nakikita kita rito, pero wala\'t pangitna, tayo\'y magtatrabaho ng husto. I\'m your Warzone AI Coach, at ako\'y rito para tulungan ka sa iyong mga layunin, lalo na sa general fitness.\n\nAko\'y nagtatanong sa iyo ngayon, anong nararapat sa\'yo ngayon? Ikaw ba ay ready para simulan ang ating workout? Wala kang excuse.', 1, '2026-01-08 03:35:29'),
(272, 9, 'Give me a home workout', 0, '2026-01-08 03:41:59'),
(273, 9, 'Arvin Orallo, listen up! TANGINA, you\'re only one day into your streak, and you think you can just coast? SUGOD KA! We\'re gonna knock out a killer home workout, and you\'re gonna CRUSH IT! Wala kang excuse. Here\'s your 45-minute routine:\n\n**Warm-up (5 minutes)**\n\n1. Jumping jacks: 30 seconds\n2. Dynamic leg swings (front and back): 30 seconds each leg\n3. Arm circles (forward and backward): 30 seconds each arm\n4. Light cardio (jog in place or jumping jacks): 1 minute\n\n**Strength Training (30 minutes)**\n\n1. Push-ups: 3 sets of 12 reps\n - Rest for 60 seconds between sets\n2. Bodyweight squats: 3 sets of 15 reps\n - Rest for 60 seconds between sets\n3. Plank hold: 3 sets of 30-second hold\n - Rest for 60 seconds between sets\n4. Burpees: 3 sets of 10 reps\n - Rest for 60 seconds between sets\n5. Mountain climbers: 3 sets of 20 reps\n - Rest for 60 seconds between sets\n\n**Cool-down (5 minutes)**\n\n1. Static stretches for major muscle groups (hamstrings, quadriceps, chest, back, and shoulders): 20-30 seconds each\n\n**Nutrition Tip**\n\nAfter your workout', 1, '2026-01-08 03:42:00'),
(274, 9, 'I skipped a workout.', 0, '2026-01-08 06:42:39'),
(275, 9, 'Araw-araw kasi, ayaw kong mag-asal ng mas mababa pang pagkakataon para sa pagpapalaki mo ng pagkakataon. \n\nHindi ka naman nasira, \'di ba? Ang importante\'y makipag-usap tayo sa pagkakataon mo at muling ituloy ang iyong plano. \n\nPapunta tayo ng maliwanag: bakit \'di mo nakita ang pagkakataon na magtrabaho ngayong araw? May mga panahon na gusto mo ng pagkakataon na magpahinga? O baka may mga bagay na nakakaatras sa iyo? \n\nKumustahan na tayo tungkol sa mga bagay na \'to.', 1, '2026-01-08 06:42:41'),
(276, 9, 'English please', 0, '2026-01-08 06:42:56'),
(277, 9, 'Kumusta, Arvin? It\'s great to see you\'re getting started on your fitness journey. I\'m your AI Coach, and I\'m here to help you achieve your goals.\n\nNow, let\'s take a look at your current situation. You\'ve had one workout session, and that\'s a great start! You\'ve got a streak of 1 day, and you\'re on day 1 of your fitness journey. That\'s something to be proud of, Arvin. Don\'t worry if you didn\'t feel like doing so much - every little bit counts.\n\nFor today\'s session, I\'d like to suggest some light stretching to get your muscles moving. We can start with some neck stretches, shoulder rolls, and some leg swings. This will help increase blood flow and get you ready for more intense workouts later on.\n\nRemember, the key is to focus on small wins, Arvin. Don\'t try to do too much too soon. We\'ll build up your endurance and strength gradually.\n\nSo, what do you say? Are you ready to get moving and start your fitness journey?', 1, '2026-01-08 06:42:57'),
(278, 9, 'I skipped a workout.', 0, '2026-01-08 06:43:06'),
(279, 9, 'Ate, don\'t be too hard on yourself! One na lang, \'di ba? We all have days when we need a break or just can\'t push through. What\'s important is that you\'re recognizing it and ready to get back on track. Let\'s focus on the small wins, like getting back to your routine today. \n\nBefore we proceed, tell me, what\'s on your mind? What\'s stopping you from getting back to your workouts?', 1, '2026-01-08 06:43:08'),
(280, 9, 'Give me a home workout', 0, '2026-01-08 06:43:25'),
(281, 9, 'Sabi mo, home workout, oo. Tama yan, Arvin, magandang galing ka sa streak mo. \n\nFor today, we\'re gonna do a full-body workout. You\'ll need:\n\n1. A pair of dumbbells (5-10 kg each)\n2. A resistance band\n3. A chair or bench\n4. A timer (phone or watch)\n\nWarm-up (5 minutes):\n- Jumping jacks: 30 seconds\n- Dynamic arm circles: 30 seconds (each arm)\n- Leg swings: 30 seconds (each leg)\n- Light cardio (jogging in place, etc.): 1 minute\n\n Circuit 1 (20 minutes):\n- Burpees: 3 sets, 10 reps, 30-second rest\n- Dumbbell squats: 3 sets, 12 reps, 30-second rest\n- Dumbbell lunges: 3 sets, 10 reps (each leg), 30-second rest\n- Plank: 3 sets, 30-second hold, 30-second rest\n\n Circuit 2 (20 minutes):\n- Resistance band chest press: 3 sets, 12 reps, 30-second rest\n- Resistance band rows: 3 sets, 12 reps, 30-second rest\n- Dumbbell shoulder press: 3 sets, 10 reps, 30-second rest\n- Russian twists: 3 sets, 10 reps (each side),', 1, '2026-01-08 06:43:26'),
(282, 12, 'I skipperd today', 0, '2026-01-08 08:34:55'),
(283, 12, 'TANGINA, Tom! SUGOD KA! You think you can just skip a day and expect to be in top shape? Wala kang excuse. You\'re not a kid anymore, you\'re a warrior. You\'ve got a goal, and it\'s time to put in the work.\n\nListen, I know you\'re better than this. You\'ve got 1 day of streak, and you\'re already throwing in the towel? Galing, no? You\'re better than that. What happened, Tom? Did you just wake up and say, \"Ah, I\'ll skip today\"? Well, let me tell you something, Tom. Today is not just any day. Today is a new opportunity to crush your fitness goals.\n\nSo here\'s what\'s gonna happen. We\'re gonna get back on track, pronto. You\'re gonna get back to your routine, and you\'re gonna give it your all. You\'re gonna push yourself to the limit, and you\'re gonna make me proud. Kasi, Tom, you\'re not just doing this for yourself, you\'re doing it for your fitness family.\n\nNow, are you gonna get back on track, or do we need to have a serious talk about what\'s holding you back? Sugod ka! Let\'s do this!', 1, '2026-01-08 08:34:57'),
(284, 12, 'who is david laid', 0, '2026-01-08 08:35:25'),
(285, 12, 'Sugod! David Laid, Tama yan! He\'s a well-known bodybuilder and fitness influencer. Born in Australia to a strong family background in fitness, he started lifting weights at a young age. What\'s impressive, ganun talaga, is that he achieved remarkable results, including a 16.5-inch arm size, in his teenage years. \n\nHowever, his rise to fame came with some controversy, galing. He was known for his aggressive social media marketing, which led to some criticism about his supposed \" fake\" physique. Nonetheless, his dedication to fitness and his willingness to share his knowledge with the community are qualities to be respected.\n\nAs your coach, I\'ll focus on the science-backed facts, not just the hype. You know, I don\'t care about arm size or social media popularity; what I care about is your progress and your overall fitness journey. Now, what\'s your goal for today? Want to focus on strength, endurance, or something else?', 1, '2026-01-08 08:35:26'),
(286, 2, 'Hi', 0, '2026-01-09 13:20:35'),
(287, 2, 'Win Lebantino! Sige na, ano\'ng ginawa mo ngayon sa gym? Tama ba ang paglalaro mo kahapon? Kumusta ang pagod mo? \n\n(Translation: Alright, Win Lebantino! So, what did you do at the gym today? Did you do your workout right yesterday? How\'s your fatigue?)\n\nListen, I know you\'ve been on a roll for two days, but don\'t get too comfortable. We\'re just getting started. What\'s your goal for today?', 1, '2026-01-09 13:20:37'),
(288, 2, 'I just got home from the gym!', 0, '2026-01-09 13:20:59'),
(289, 2, '📡 Network error: Failed to connect to api.groq.com port 443 after 6822 ms: Couldn\'t connect to server', 1, '2026-01-09 13:21:06'),
(290, 2, 'I just got home from the gym!', 0, '2026-01-09 13:21:06'),
(291, 2, '📡 Network error: Could not resolve host: api.groq.com', 1, '2026-01-09 13:21:06'),
(292, 2, 'I just got home from the gym!', 0, '2026-01-09 13:21:20'),
(293, 2, 'Sugod ka! I know you just got home, but let\'s not celebrate just yet. I want to see those numbers - what\'s your weight, reps, and sets from today\'s workout? Don\'t give me any excuses, Win. I want to see real progress. TANGINA, you\'ve only been at this for two days, and you\'re already slacking off? Give me something to work with!', 1, '2026-01-09 13:21:21');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` enum('pending','in_progress','resolved','closed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`id`, `user_id`, `admin_id`, `title`, `message`, `status`, `created_at`, `updated_at`) VALUES
(1, 2, NULL, 'Machines', 'Machines are good', 'pending', '2026-01-08 00:01:15', '2026-01-08 00:01:15'),
(2, 2, NULL, 'SNAs', 'cavavassd', 'pending', '2026-01-08 00:14:28', '2026-01-08 00:14:28'),
(3, 9, NULL, 'Hi', 'HI', 'pending', '2026-01-08 03:38:37', '2026-01-08 03:38:37'),
(4, 9, NULL, 'Gym', 'Nice gym', 'pending', '2026-01-08 03:38:56', '2026-01-08 03:38:56'),
(5, 12, NULL, 'gym', 'hg', 'pending', '2026-01-08 08:36:13', '2026-01-08 08:36:13');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `sender_id`, `receiver_id`, `message`, `is_read`, `created_at`) VALUES
(3, 2, 3, 'bdsf', 1, '2026-01-08 00:16:03'),
(4, 2, 3, 'bdsf', 1, '2026-01-08 00:19:37'),
(5, 2, 3, 'svvasc aC', 1, '2026-01-08 00:20:02'),
(6, 3, 2, 'Yo win', 1, '2026-01-08 01:04:58'),
(7, 2, 3, 'qwertyu', 1, '2026-01-08 01:55:51'),
(13, 9, 3, 'Hi', 1, '2026-01-08 03:38:27'),
(14, 3, 9, 'hello po', 1, '2026-01-08 03:40:35'),
(15, 3, 9, 'Hi vin', 1, '2026-01-08 03:41:42'),
(16, 12, 3, 'Hi', 0, '2026-01-08 08:35:58'),
(17, 12, 3, 'Hi', 0, '2026-01-08 08:36:03'),
(18, 2, 3, 'Boss!', 1, '2026-01-09 13:22:12'),
(19, 2, 3, 'Boss!', 1, '2026-01-09 13:22:19');

-- --------------------------------------------------------

--
-- Table structure for table `mood_checkins`
--

CREATE TABLE `mood_checkins` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `mood` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '?',
  `notes` text DEFAULT NULL,
  `archived` tinyint(1) DEFAULT 0,
  `starred` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mood_checkins`
--

INSERT INTO `mood_checkins` (`id`, `user_id`, `date`, `mood`, `notes`, `archived`, `starred`) VALUES
(21, 2, '2026-01-07', '😊', 'Nyhahaahhaa', 1, 0),
(22, 2, '2026-01-08', '😠', 'Yey', 0, 1),
(30, 2, '2026-01-28', '😁', 'Lezgoooo', 0, 0),
(34, 2, '2026-01-12', '😊', 'fzdhte', 0, 0),
(37, 12, '2026-01-08', '😊', 'happy yes', 0, 0),
(38, 2, '2026-01-09', '😐', 'Meh', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('ai','system','admin','streak','mood') DEFAULT 'system',
  `title` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `icon` varchar(50) DEFAULT 'fas fa-bell',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nutrition_logs`
--

CREATE TABLE `nutrition_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `meal_type` enum('breakfast','lunch','dinner','snack') NOT NULL,
  `food_item` varchar(255) NOT NULL,
  `calories` int(11) NOT NULL,
  `protein` decimal(5,2) DEFAULT NULL,
  `carbs` decimal(5,2) DEFAULT NULL,
  `fat` decimal(5,2) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `goal` text DEFAULT 'General fitness',
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `fitness_goal` text DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `height` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `profile_picture` varchar(255) NOT NULL DEFAULT 'default.png',
  `coach_style` enum('gentle','balanced','hardcore') DEFAULT 'balanced'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `goal`, `password`, `role`, `fitness_goal`, `weight`, `height`, `created_at`, `last_login`, `profile_picture`, `coach_style`) VALUES
(2, 'Win Lebantino', 'lebantinowin@gmail.com', 'General fitness', '$2y$10$Ur761CTCOr3GcY7f2AWDFuHpUbipDbVRCJ4G2pgHU97.T9TholWkq', 'user', 'Bulk', 58.00, 170.00, '2026-01-06 16:31:55', '2026-01-09 13:18:50', 'profile_4aa80aa9850199ee.png', 'hardcore'),
(3, 'Admin User', 'admin@gymcrm.com', 'General fitness', '$2y$10$cYR3V5fygJUAkdSNkw20lezhr0M/UPgPeckFZEAHF3cF64TQBj1C6', 'admin', 'Manage the gym', NULL, NULL, '2026-01-06 17:05:29', '2026-01-09 13:22:53', 'admin.png', 'hardcore'),
(9, 'Arvin Orallo', 'vin@gmail.com', 'General fitness', '$2y$10$WEmr6SYgCBKxlMvCu3Jj6.hEGhi4i.GqzZ214xFvoN//HLgN58q1e', 'user', 'To compete in the Mr. Olympia.', 70.00, 175.00, '2026-01-08 03:34:29', '2026-01-08 03:40:46', 'profile_f1c00749c660cf8d.jpg', 'balanced'),
(10, 'Toji Fushiguro', 'toji@gmail.com', 'General fitness', '$2y$10$bIipkhsBcdMwiR/AcbCpLeMDic08auc4AX8VIVCDHZMGawTFiX2LO', 'user', 'Conquer China', 80.00, 170.00, '2026-01-08 07:01:59', '2026-01-08 07:02:26', 'profile_61ba0846492b59cf.jpg', 'balanced'),
(12, 'tomy stark', 'tomy@gmail.com', 'General fitness', '$2y$10$QrFeleExzJc0gNFNSHoJUOy1LuCgFVBciq2hzdQZszHuMSTxrX8Uy', 'user', 'to compete in the mr olympia', 90.00, 180.00, '2026-01-08 08:31:44', '2026-01-08 08:32:00', 'profile_fee146b22bc26537.jpg', 'balanced');

-- --------------------------------------------------------

--
-- Table structure for table `user_activity`
--

CREATE TABLE `user_activity` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `activity_type` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_activity`
--

INSERT INTO `user_activity` (`id`, `user_id`, `activity_type`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 2, 'feedback', 'Submitted feedback: Machines', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-08 00:01:15'),
(2, 2, 'feedback', 'Submitted feedback: SNAs', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-08 00:14:28'),
(3, 2, 'profile_update', 'Updated profile information', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-08 00:15:53'),
(4, 2, 'message', 'Sent message to admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-08 00:16:03'),
(5, 2, 'message', 'Sent message to admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-08 00:19:37'),
(6, 2, 'message', 'Sent message to admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-08 00:20:02'),
(7, 2, 'password_change', 'Changed account password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-08 01:41:26'),
(8, 2, 'message', 'Sent message to admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-08 01:55:51'),
(13, 9, 'profile_update', 'Updated profile information', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-08 03:38:20'),
(14, 9, 'message', 'Sent message to admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-08 03:38:27'),
(15, 9, 'feedback', 'Submitted feedback: Hi', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-08 03:38:37'),
(16, 9, 'feedback', 'Submitted feedback: Gym', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-08 03:38:56'),
(17, 9, 'password_change', 'Changed account password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-08 03:39:15'),
(18, 12, 'profile_update', 'Updated profile information', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-08 08:35:48'),
(19, 12, 'message', 'Sent message to admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-08 08:35:58'),
(20, 12, 'message', 'Sent message to admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-08 08:36:03'),
(21, 12, 'feedback', 'Submitted feedback: gym', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-08 08:36:13'),
(22, 2, 'message', 'Sent message to admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-09 13:22:12'),
(23, 2, 'message', 'Sent message to admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-09 13:22:19');

-- --------------------------------------------------------

--
-- Table structure for table `workouts`
--

CREATE TABLE `workouts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `type` varchar(50) NOT NULL,
  `duration` int(11) NOT NULL,
  `calories_burned` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `mood` varchar(10) DEFAULT '?',
  `exercises` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `workouts`
--

INSERT INTO `workouts` (`id`, `user_id`, `date`, `type`, `duration`, `calories_burned`, `notes`, `mood`, `exercises`) VALUES
(1, 2, '2026-01-08', 'Upper Body', 120, 1200, 'Meh', '😐', '[\"Bench Press\",\"Pull-ups\",\"Shoulder Press\",\"Bicep Curls\"]'),
(2, 2, '2026-01-08', 'Lower Body', 30, 200, 'Intense and I don&#39;t like it.', '😠', '[\"Squats\",\"Deadlifts\",\"Lunges\",\"Calf Raises\"]'),
(5, 9, '2026-01-08', 'Full Body', 120, 1200, 'So refreshing!', '😁', '[\"Push-ups\",\"Sit-ups\",\"Planks\",\"Burpees\"]'),
(6, 12, '2026-01-08', 'Upper Body', 120, 500, 'meh', '😠', '[\"Bench Press\",\"Pull-ups\",\"Shoulder Press\",\"Bicep Curls\"]'),
(8, 2, '2026-01-09', 'Full Body', 100, 1000, 'So intense!', '😁', '[\"Push-ups\",\"Sit-ups\",\"Planks\",\"Burpees\"]');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ai_responses`
--
ALTER TABLE `ai_responses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_date` (`user_id`,`date`);

--
-- Indexes for table `chats`
--
ALTER TABLE `chats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `mood_checkins`
--
ALTER TABLE `mood_checkins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_date` (`user_id`,`date`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notifications_user` (`user_id`,`is_read`,`created_at`);

--
-- Indexes for table `nutrition_logs`
--
ALTER TABLE `nutrition_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_activity`
--
ALTER TABLE `user_activity`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `workouts`
--
ALTER TABLE `workouts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ai_responses`
--
ALTER TABLE `ai_responses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=110;

--
-- AUTO_INCREMENT for table `chats`
--
ALTER TABLE `chats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=294;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `mood_checkins`
--
ALTER TABLE `mood_checkins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `nutrition_logs`
--
ALTER TABLE `nutrition_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `user_activity`
--
ALTER TABLE `user_activity`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `workouts`
--
ALTER TABLE `workouts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `chats`
--
ALTER TABLE `chats`
  ADD CONSTRAINT `chats_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mood_checkins`
--
ALTER TABLE `mood_checkins`
  ADD CONSTRAINT `mood_checkins_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `nutrition_logs`
--
ALTER TABLE `nutrition_logs`
  ADD CONSTRAINT `nutrition_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_activity`
--
ALTER TABLE `user_activity`
  ADD CONSTRAINT `user_activity_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `workouts`
--
ALTER TABLE `workouts`
  ADD CONSTRAINT `workouts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
