# Mafiascum Votecounter Extension

## Getting Started

This requires the BBCode extension. I also recommend that you use a docker build to install it. 

### Installing 

This should automatically install based upon the recommended install path for the environment. 
You will need to turn on the extension from the ACP. It can also be disabled from there as well.
If disabled, the BBCodes that are a part of this will seem to do nothing. 

## BBCodes included

```
Votecount 
```
That BBCode will give a votecount directly out the the user. In preview mode you can see how it will look (and see if there are any issues/ mistakes). 

```
VotecountBBCode
```
This will output the BBCode that would be output by the original votecount tag. This allows for user customization.

## How to use it

In post 0, You'll need to have a spoiler=VoteCount Settings

And then have the settings within that spoiler. The specific entries can be put in any order. 
An entry is in the format entry=value

###Prompt List


Anything with true is required. False is optional
 		array_push($staticPromptArray, array(votecountSettings::PLAYER_TEXT_PROMPT, true));
        array_push($staticPromptArray, array(votecountSettings::REPLACEMENTS_LIST_PROMPT, false));
        array_push($staticPromptArray, array(votecountSettings::MOD_LIST_PROMPT, true));
        array_push($staticPromptArray, array(votecountSettings::DAY_NUMBERS_PROMPT, false));
        array_push($staticPromptArray, array(votecountSettings::DEAD_LIST_PROMPT, false));
        array_push($staticPromptArray, array(votecountSettings::DEADLINE_PROMPT, true));
        array_push($staticPromptArray, array(votecountSettings::VOTE_NUMBER_INPUT, false));
        array_push($staticPromptArray, array(votecountSettings::COLOR_HASH_CODE, false));
        array_push($staticPromptArray, array(votecountSettings::PROD_TIMER, false));
        array_push($staticPromptArray, array(votecountSettings::FONT_OVERRIDE, false));
        array_push($staticPromptArray, array(votecountSettings::DAY_VIGGED_PLAYERS_PROMPT, false));
        array_push($staticPromptArray, array(votecountSettings::MOD_KILLED_PLAYERS_PROMPT, false));
        array_push($staticPromptArray, array(votecountSettings::RESURRECTED_PLAYERS_PROMPT, false));
        array_push($staticPromptArray, array(votecountSettings::LYLO_OR_MYLO_NUMBERS_PROMPT, false));
        array_push($staticPromptArray, array(votecountSettings::PLAYER_MODIFIER_ARRAY_PROMPT,false));
        
Almost all lists are comma delimited. For more details see: https://forum.mafiascum.net/viewtopic.php?p=9979404&user_select%5B%5D=22566#p9979404
Until I can get time to put more details, that is an accurate summary except for the additional modkill prompt which follows the same format as the dayvigged/resurrected.

Lylo-mylo modifiers also function slightly different and player modifiers a sample is below:

lyloOrMyloPostNumbers=0-false
playerModifiers=testA-hated-0-true,testB-loved-0-true,testC-treestump-0-true,testC-treestump-3-false

Eventually if/when this has database backing these will be invisible. 


## Versioning
Pull request 9 alpha version 0.1



