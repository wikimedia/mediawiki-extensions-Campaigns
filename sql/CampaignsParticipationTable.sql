-- Table for participations in a campaign
CREATE TABLE IF NOT EXISTS /*_*/campaigns_participation (

	-- Auto-increment id
	participation_id int unsigned NOT NULL PRIMARY KEY auto_increment,

	-- User ID, foreign key on user.user_id
	participation_user_id INT unsigned NOT NULL,

	-- Campaign ID, foreign key on campaigns_campaign.campaign_id
	participation_campaign_id INT unsigned NOT NULL,

	-- Time the user joined the campaign
	participation_time_joined varbinary(14) NOT NULL,

	-- Time the user left the campaign
	participation_time_left varbinary(14),

	-- Flag for campaign organizers
	participation_organizer boolean NOT NULL default false

) /*$wgDBTableOptions*/;

-- Indexes
CREATE UNIQUE INDEX /*i*/campaigns_participation_id ON
	/*_*/campaigns_participation (participation_id);

CREATE INDEX /*i*/campaigns_participation_user_id ON
	/*_*/campaigns_participation (participation_user_id);

CREATE INDEX /*i*/campaigns_participation_camp_id ON
	/*_*/campaigns_participation (participation_campaign_id);

CREATE INDEX /*i*/campaigns_participation_time_left ON
	/*_*/campaigns_participation (participation_time_left);

CREATE INDEX /*i*/campaigns_participation_organizer ON
	/*_*/campaigns_participation (participation_organizer);