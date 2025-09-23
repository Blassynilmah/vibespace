-- Table: public.attachments

-- DROP TABLE IF EXISTS public.attachments;

CREATE TABLE IF NOT EXISTS public.attachments
(
    id bigint NOT NULL GENERATED ALWAYS AS IDENTITY ( INCREMENT 1 START 50 MINVALUE 1 MAXVALUE 9223372036854775807 CACHE 1 ),
    sender_id bigint NOT NULL,
    receiver_id bigint,
    message_id bigint,
    file_path character varying(255) COLLATE pg_catalog."default" NOT NULL,
    file_name character varying(255) COLLATE pg_catalog."default" DEFAULT NULL::character varying,
    mime_type character varying(255) COLLATE pg_catalog."default" DEFAULT NULL::character varying,
    size bigint,
    content_type character varying(30) COLLATE pg_catalog."default" DEFAULT NULL::character varying,
    file_type character varying(30) COLLATE pg_catalog."default" DEFAULT NULL::character varying,
    meta text COLLATE pg_catalog."default",
    created_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone DEFAULT now(),
    CONSTRAINT attachments_id_check CHECK (id > 0),
    CONSTRAINT attachments_sender_id_check CHECK (sender_id > 0),
    CONSTRAINT attachments_receiver_id_check CHECK (receiver_id > 0),
    CONSTRAINT attachments_message_id_check CHECK (message_id > 0),
    CONSTRAINT attachments_size_check CHECK (size > 0),
    CONSTRAINT attachments_content_type_check CHECK (content_type::text = ANY (ARRAY['safe'::character varying, 'adult'::character varying]::text[])),
    CONSTRAINT attachments_file_type_check CHECK (file_type::text = ANY (ARRAY['image'::character varying, 'video'::character varying]::text[]))
)

TABLESPACE pg_default;

ALTER TABLE IF EXISTS public.attachments
    OWNER to postgres;

-- Table: public.blocks

-- DROP TABLE IF EXISTS public.blocks;

CREATE TABLE IF NOT EXISTS public.blocks
(
    id bigint NOT NULL GENERATED ALWAYS AS IDENTITY ( INCREMENT 1 START 1 MINVALUE 1 MAXVALUE 9223372036854775807 CACHE 1 ),
    blocker_id bigint NOT NULL,
    blocked_id bigint NOT NULL,
    block_type character varying(20) COLLATE pg_catalog."default" NOT NULL DEFAULT 'message'::character varying,
    blocked_at timestamp(0) without time zone NOT NULL DEFAULT now(),
    CONSTRAINT blocks_pkey PRIMARY KEY (id),
    CONSTRAINT blocks_unique UNIQUE (blocker_id, blocked_id),
    CONSTRAINT blocks_blocked_id_foreign FOREIGN KEY (blocked_id)
        REFERENCES public.users (id) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE CASCADE,
    CONSTRAINT blocks_blocker_id_foreign FOREIGN KEY (blocker_id)
        REFERENCES public.users (id) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE CASCADE
)

TABLESPACE pg_default;

ALTER TABLE IF EXISTS public.blocks
    OWNER to postgres;
-- Index: idx_blocks_blocker_blocked

-- DROP INDEX IF EXISTS public.idx_blocks_blocker_blocked;

CREATE INDEX IF NOT EXISTS idx_blocks_blocker_blocked
    ON public.blocks USING btree
    (blocker_id ASC NULLS LAST, blocked_id ASC NULLS LAST)
    WITH (fillfactor=100, deduplicate_items=True)
    TABLESPACE pg_default;

-- Table: public.cache

-- DROP TABLE IF EXISTS public.cache;

CREATE TABLE IF NOT EXISTS public.cache
(
    key character varying(255) COLLATE pg_catalog."default" NOT NULL,
    value text COLLATE pg_catalog."default" NOT NULL,
    expiration integer NOT NULL,
    CONSTRAINT cache_pkey PRIMARY KEY (key)
)

TABLESPACE pg_default;

ALTER TABLE IF EXISTS public.cache
    OWNER to postgres;

-- Table: public.cache_locks

-- DROP TABLE IF EXISTS public.cache_locks;

CREATE TABLE IF NOT EXISTS public.cache_locks
(
    key character varying(255) COLLATE pg_catalog."default" NOT NULL,
    owner character varying(255) COLLATE pg_catalog."default" NOT NULL,
    expiration integer NOT NULL,
    CONSTRAINT cache_locks_pkey PRIMARY KEY (key)
)

TABLESPACE pg_default;

ALTER TABLE IF EXISTS public.cache_locks
    OWNER to postgres;

-- Table: public.comment_reactions

-- DROP TABLE IF EXISTS public.comment_reactions;

CREATE TABLE IF NOT EXISTS public.comment_reactions
(
    id bigint NOT NULL GENERATED ALWAYS AS IDENTITY ( INCREMENT 1 START 50 MINVALUE 1 MAXVALUE 9223372036854775807 CACHE 1 ),
    user_id bigint NOT NULL,
    comment_id bigint NOT NULL,
    type character varying(30) COLLATE pg_catalog."default" NOT NULL,
    created_at timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
    updated_at timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
    CONSTRAINT comment_reactions_pkey PRIMARY KEY (id),
    CONSTRAINT comment_reactions_comment_id_foreign FOREIGN KEY (comment_id)
        REFERENCES public.comments (id) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE CASCADE,
    CONSTRAINT comment_reactions_id_check CHECK (id > 0),
    CONSTRAINT comment_reactions_user_id_check CHECK (user_id > 0),
    CONSTRAINT comment_reactions_comment_id_check CHECK (comment_id > 0),
    CONSTRAINT comment_reactions_type_check CHECK (type::text = ANY (ARRAY['like'::character varying, 'dislike'::character varying]::text[]))
)

TABLESPACE pg_default;

ALTER TABLE IF EXISTS public.comment_reactions
    OWNER to postgres;

-- Table: public.comments

-- DROP TABLE IF EXISTS public.comments;

CREATE TABLE IF NOT EXISTS public.comments
(
    id bigint NOT NULL GENERATED ALWAYS AS IDENTITY ( INCREMENT 1 START 1000 MINVALUE 1 MAXVALUE 9223372036854775807 CACHE 1 ),
    mood_board_id bigint NOT NULL,
    user_id bigint NOT NULL,
    body text COLLATE pg_catalog."default" NOT NULL,
    created_at timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
    updated_at timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
    CONSTRAINT comments_pkey PRIMARY KEY (id),
    CONSTRAINT comments_mood_board_id_foreign FOREIGN KEY (mood_board_id)
        REFERENCES public.mood_boards (id) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE CASCADE,
    CONSTRAINT comments_id_check CHECK (id > 0),
    CONSTRAINT comments_mood_board_id_check CHECK (mood_board_id > 0),
    CONSTRAINT comments_user_id_check CHECK (user_id > 0)
)

TABLESPACE pg_default;

ALTER TABLE IF EXISTS public.comments
    OWNER to postgres;

-- Table: public.favorite_teasers

-- DROP TABLE IF EXISTS public.favorite_teasers;

CREATE TABLE IF NOT EXISTS public.favorite_teasers
(
    id bigint NOT NULL DEFAULT nextval('favorite_teasers_id_seq'::regclass),
    teaser_id bigint NOT NULL,
    user_id bigint NOT NULL,
    CONSTRAINT favorite_teasers_pkey PRIMARY KEY (id),
    CONSTRAINT favorite_teasers_teaser_id_user_id_key UNIQUE (teaser_id, user_id),
    CONSTRAINT favorite_teasers_teaser_id_fkey FOREIGN KEY (teaser_id)
        REFERENCES public.teasers (id) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE CASCADE,
    CONSTRAINT favorite_teasers_user_id_fkey FOREIGN KEY (user_id)
        REFERENCES public.users (id) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE CASCADE
)

TABLESPACE pg_default;

ALTER TABLE IF EXISTS public.favorite_teasers
    OWNER to postgres;

-- Table: public.file_list_items

-- DROP TABLE IF EXISTS public.file_list_items;

CREATE TABLE IF NOT EXISTS public.file_list_items
(
    id bigint NOT NULL GENERATED ALWAYS AS IDENTITY ( INCREMENT 1 START 1000 MINVALUE 1 MAXVALUE 9223372036854775807 CACHE 1 ),
    file_list_id bigint NOT NULL,
    file_id bigint NOT NULL,
    created_at timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
    updated_at timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
    CONSTRAINT file_list_items_pkey PRIMARY KEY (id),
    CONSTRAINT file_list_items_file_list_id_foreign FOREIGN KEY (file_list_id)
        REFERENCES public.file_lists (id) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE CASCADE,
    CONSTRAINT file_list_items_id_check CHECK (id > 0),
    CONSTRAINT file_list_items_file_list_id_check CHECK (file_list_id > 0),
    CONSTRAINT file_list_items_file_id_check CHECK (file_id > 0)
)

TABLESPACE pg_default;

ALTER TABLE IF EXISTS public.file_list_items
    OWNER to postgres;

-- Table: public.file_lists

-- DROP TABLE IF EXISTS public.file_lists;

CREATE TABLE IF NOT EXISTS public.file_lists
(
    id bigint NOT NULL GENERATED ALWAYS AS IDENTITY ( INCREMENT 1 START 1000 MINVALUE 1 MAXVALUE 9223372036854775807 CACHE 1 ),
    user_id bigint NOT NULL,
    name character varying(255) COLLATE pg_catalog."default" NOT NULL,
    created_at timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
    updated_at timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
    CONSTRAINT file_lists_pkey PRIMARY KEY (id),
    CONSTRAINT file_lists_user_id_foreign FOREIGN KEY (user_id)
        REFERENCES public.users (id) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE CASCADE,
    CONSTRAINT file_lists_id_check CHECK (id > 0),
    CONSTRAINT file_lists_user_id_check CHECK (user_id > 0)
)

TABLESPACE pg_default;

ALTER TABLE IF EXISTS public.file_lists
    OWNER to postgres;

-- Table: public.follows

-- DROP TABLE IF EXISTS public.follows;

CREATE TABLE IF NOT EXISTS public.follows
(
    id bigint NOT NULL GENERATED ALWAYS AS IDENTITY ( INCREMENT 1 START 1000 MINVALUE 1 MAXVALUE 9223372036854775807 CACHE 1 ),
    follower_id bigint NOT NULL,
    following_id bigint NOT NULL,
    created_at timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
    updated_at timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
    CONSTRAINT follows_pkey PRIMARY KEY (id),
    CONSTRAINT follows_follower_id_foreign FOREIGN KEY (follower_id)
        REFERENCES public.users (id) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE CASCADE,
    CONSTRAINT follows_following_id_foreign FOREIGN KEY (following_id)
        REFERENCES public.users (id) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE CASCADE,
    CONSTRAINT follows_id_check CHECK (id > 0),
    CONSTRAINT follows_follower_id_check CHECK (follower_id > 0),
    CONSTRAINT follows_following_id_check CHECK (following_id > 0)
)

TABLESPACE pg_default;

ALTER TABLE IF EXISTS public.follows
    OWNER to postgres;

-- Table: public.messages

-- DROP TABLE IF EXISTS public.messages;

CREATE TABLE IF NOT EXISTS public.messages
(
    id bigint NOT NULL GENERATED ALWAYS AS IDENTITY ( INCREMENT 1 START 1000 MINVALUE 1 MAXVALUE 9223372036854775807 CACHE 1 ),
    sender_id bigint NOT NULL,
    receiver_id bigint NOT NULL,
    file_id bigint,
    body text COLLATE pg_catalog."default" NOT NULL,
    created_at timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
    updated_at timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
    is_read smallint NOT NULL DEFAULT 0,
    CONSTRAINT messages_pkey PRIMARY KEY (id),
    CONSTRAINT messages_file_id_foreign FOREIGN KEY (file_id)
        REFERENCES public.user_files (id) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE NO ACTION,
    CONSTRAINT messages_receiver_id_foreign FOREIGN KEY (receiver_id)
        REFERENCES public.users (id) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE CASCADE,
    CONSTRAINT messages_sender_id_foreign FOREIGN KEY (sender_id)
        REFERENCES public.users (id) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE CASCADE,
    CONSTRAINT messages_id_check CHECK (id > 0),
    CONSTRAINT messages_sender_id_check CHECK (sender_id > 0),
    CONSTRAINT messages_receiver_id_check CHECK (receiver_id > 0),
    CONSTRAINT messages_file_id_check CHECK (file_id > 0)
)

TABLESPACE pg_default;

ALTER TABLE IF EXISTS public.messages
    OWNER to postgres;

-- Table: public.migrations

-- DROP TABLE IF EXISTS public.migrations;

CREATE TABLE IF NOT EXISTS public.migrations
(
    id integer NOT NULL GENERATED ALWAYS AS IDENTITY ( INCREMENT 1 START 1000 MINVALUE 1 MAXVALUE 2147483647 CACHE 1 ),
    migration character varying(255) COLLATE pg_catalog."default" NOT NULL,
    batch integer NOT NULL,
    CONSTRAINT migrations_pkey PRIMARY KEY (id),
    CONSTRAINT migrations_id_check CHECK (id > 0)
)

TABLESPACE pg_default;

ALTER TABLE IF EXISTS public.migrations
    OWNER to postgres;

-- Table: public.mood_boards

-- DROP TABLE IF EXISTS public.mood_boards;

CREATE TABLE IF NOT EXISTS public.mood_boards
(
    id bigint NOT NULL GENERATED ALWAYS AS IDENTITY ( INCREMENT 1 START 1000 MINVALUE 1 MAXVALUE 9223372036854775807 CACHE 1 ),
    user_id bigint NOT NULL,
    title character varying(255) COLLATE pg_catalog."default" DEFAULT NULL::character varying,
    description text COLLATE pg_catalog."default",
    latest_mood character varying(30) COLLATE pg_catalog."default" DEFAULT NULL::character varying,
    image text COLLATE pg_catalog."default",
    video character varying(255) COLLATE pg_catalog."default" DEFAULT NULL::character varying,
    created_at timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
    updated_at timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
    CONSTRAINT mood_boards_pkey PRIMARY KEY (id),
    CONSTRAINT mood_boards_user_id_foreign FOREIGN KEY (user_id)
        REFERENCES public.users (id) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE CASCADE,
    CONSTRAINT mood_boards_id_check CHECK (id > 0),
    CONSTRAINT mood_boards_user_id_check CHECK (user_id > 0),
    CONSTRAINT mood_boards_latest_mood_check CHECK (latest_mood::text = ANY (ARRAY[''::character varying, 'excited'::character varying, 'happy'::character varying, 'chill'::character varying, 'thoughtful'::character varying, 'sad'::character varying, 'flirty'::character varying, 'mindblown'::character varying, 'love'::character varying]::text[]))
)

TABLESPACE pg_default;

ALTER TABLE IF EXISTS public.mood_boards
    OWNER to postgres;

-- Table: public.mutes

-- DROP TABLE IF EXISTS public.mutes;

CREATE TABLE IF NOT EXISTS public.mutes
(
    id bigint NOT NULL GENERATED ALWAYS AS IDENTITY ( INCREMENT 1 START 1 MINVALUE 1 MAXVALUE 9223372036854775807 CACHE 1 ),
    muter_id bigint NOT NULL,
    muted_id bigint NOT NULL,
    muted_at timestamp(0) without time zone NOT NULL DEFAULT now(),
    mute_until timestamp(0) without time zone,
    CONSTRAINT mutes_pkey PRIMARY KEY (id),
    CONSTRAINT mutes_unique UNIQUE (muter_id, muted_id),
    CONSTRAINT mutes_muted_id_foreign FOREIGN KEY (muted_id)
        REFERENCES public.users (id) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE CASCADE,
    CONSTRAINT mutes_muter_id_foreign FOREIGN KEY (muter_id)
        REFERENCES public.users (id) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE CASCADE
)

TABLESPACE pg_default;

ALTER TABLE IF EXISTS public.mutes
    OWNER to postgres;

-- Table: public.notifications

-- DROP TABLE IF EXISTS public.notifications;

CREATE TABLE IF NOT EXISTS public.notifications
(
    id bigint NOT NULL GENERATED ALWAYS AS IDENTITY ( INCREMENT 1 START 1000 MINVALUE 1 MAXVALUE 9223372036854775807 CACHE 1 ),
    user_id bigint NOT NULL,
    reactor_id bigint,
    third_party_ids text COLLATE pg_catalog."default",
    third_party_message text COLLATE pg_catalog."default",
    type character varying(255) COLLATE pg_catalog."default",
    data jsonb NOT NULL,
    is_read boolean NOT NULL DEFAULT false,
    read_at timestamp without time zone,
    created_at timestamp without time zone NOT NULL,
    updated_at timestamp without time zone NOT NULL,
    CONSTRAINT notifications_pkey PRIMARY KEY (id),
    CONSTRAINT notifications_id_check CHECK (id > 0),
    CONSTRAINT notifications_user_id_check CHECK (user_id > 0)
)

TABLESPACE pg_default;

ALTER TABLE IF EXISTS public.notifications
    OWNER to postgres;

-- Table: public.password_reset_tokens

-- DROP TABLE IF EXISTS public.password_reset_tokens;

CREATE TABLE IF NOT EXISTS public.password_reset_tokens
(
    email character varying(255) COLLATE pg_catalog."default" NOT NULL,
    token character varying(255) COLLATE pg_catalog."default" NOT NULL,
    created_at timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
    CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email)
)

TABLESPACE pg_default;

ALTER TABLE IF EXISTS public.password_reset_tokens
    OWNER to postgres;

-- Table: public.personal_access_tokens

-- DROP TABLE IF EXISTS public.personal_access_tokens;

CREATE TABLE IF NOT EXISTS public.personal_access_tokens
(
    id bigint NOT NULL GENERATED ALWAYS AS IDENTITY ( INCREMENT 1 START 1000 MINVALUE 1 MAXVALUE 9223372036854775807 CACHE 1 ),
    tokenable_type character varying(255) COLLATE pg_catalog."default" NOT NULL,
    tokenable_id bigint NOT NULL,
    name text COLLATE pg_catalog."default" NOT NULL,
    token character varying(64) COLLATE pg_catalog."default" NOT NULL,
    abilities text COLLATE pg_catalog."default",
    last_used_at timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
    expires_at timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
    created_at timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
    updated_at timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
    CONSTRAINT personal_access_tokens_pkey PRIMARY KEY (id),
    CONSTRAINT personal_access_tokens_id_check CHECK (id > 0),
    CONSTRAINT personal_access_tokens_tokenable_id_check CHECK (tokenable_id > 0)
)

TABLESPACE pg_default;

ALTER TABLE IF EXISTS public.personal_access_tokens
    OWNER to postgres;

-- Table: public.posts

-- DROP TABLE IF EXISTS public.posts;

CREATE TABLE IF NOT EXISTS public.posts
(
    id bigint NOT NULL GENERATED ALWAYS AS IDENTITY ( INCREMENT 1 START 1000 MINVALUE 1 MAXVALUE 9223372036854775807 CACHE 1 ),
    mood_board_id bigint NOT NULL,
    type character varying(30) COLLATE pg_catalog."default" NOT NULL,
    content text COLLATE pg_catalog."default" NOT NULL,
    caption character varying(255) COLLATE pg_catalog."default" DEFAULT NULL::character varying,
    created_at timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
    updated_at timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
    CONSTRAINT posts_pkey PRIMARY KEY (id),
    CONSTRAINT posts_mood_board_id_foreign FOREIGN KEY (mood_board_id)
        REFERENCES public.mood_boards (id) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE CASCADE,
    CONSTRAINT posts_id_check CHECK (id > 0),
    CONSTRAINT posts_mood_board_id_check CHECK (mood_board_id > 0),
    CONSTRAINT posts_type_check CHECK (type::text = ANY (ARRAY['image'::character varying, 'video'::character varying, 'text'::character varying]::text[]))
)

TABLESPACE pg_default;

ALTER TABLE IF EXISTS public.posts
    OWNER to postgres;

-- Table: public.profile_pictures

-- DROP TABLE IF EXISTS public.profile_pictures;

CREATE TABLE IF NOT EXISTS public.profile_pictures
(
    id bigint NOT NULL GENERATED ALWAYS AS IDENTITY ( INCREMENT 1 START 1000 MINVALUE 1 MAXVALUE 9223372036854775807 CACHE 1 ),
    user_id bigint NOT NULL,
    path character varying(255) COLLATE pg_catalog."default" NOT NULL,
    created_at timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
    updated_at timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
    CONSTRAINT profile_pictures_pkey PRIMARY KEY (id),
    CONSTRAINT profile_pictures_user_id_foreign FOREIGN KEY (user_id)
        REFERENCES public.users (id) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE CASCADE,
    CONSTRAINT profile_pictures_id_check CHECK (id > 0),
    CONSTRAINT profile_pictures_user_id_check CHECK (user_id > 0)
)

TABLESPACE pg_default;

ALTER TABLE IF EXISTS public.profile_pictures
    OWNER to postgres;

-- Table: public.reactions

-- DROP TABLE IF EXISTS public.reactions;

CREATE TABLE IF NOT EXISTS public.reactions
(
    id bigint NOT NULL GENERATED ALWAYS AS IDENTITY ( INCREMENT 1 START 1000 MINVALUE 1 MAXVALUE 9223372036854775807 CACHE 1 ),
    user_id bigint NOT NULL,
    mood_board_id bigint NOT NULL,
    mood character varying(30) COLLATE pg_catalog."default" NOT NULL DEFAULT 'flirty'::character varying,
    created_at timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
    updated_at timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
    CONSTRAINT reactions_pkey PRIMARY KEY (id),
    CONSTRAINT reactions_mood_board_id_foreign FOREIGN KEY (mood_board_id)
        REFERENCES public.mood_boards (id) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE CASCADE,
    CONSTRAINT reactions_id_check CHECK (id > 0),
    CONSTRAINT reactions_user_id_check CHECK (user_id > 0),
    CONSTRAINT reactions_mood_board_id_check CHECK (mood_board_id > 0),
    CONSTRAINT reactions_mood_check CHECK (mood::text = ANY (ARRAY['fire'::character varying, 'love'::character varying, 'funny'::character varying, 'mind-blown'::character varying, 'cool'::character varying, 'crying'::character varying, 'clap'::character varying, 'flirty'::character varying]::text[]))
)

TABLESPACE pg_default;

ALTER TABLE IF EXISTS public.reactions
    OWNER to postgres;

-- Table: public.replies

-- DROP TABLE IF EXISTS public.replies;

CREATE TABLE IF NOT EXISTS public.replies
(
    id bigint NOT NULL GENERATED ALWAYS AS IDENTITY ( INCREMENT 1 START 1000 MINVALUE 1 MAXVALUE 9223372036854775807 CACHE 1 ),
    comment_id bigint NOT NULL,
    user_id bigint NOT NULL,
    body text COLLATE pg_catalog."default" NOT NULL,
    likes integer NOT NULL DEFAULT 0,
    dislikes integer NOT NULL DEFAULT 0,
    created_at timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
    updated_at timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
    CONSTRAINT replies_pkey PRIMARY KEY (id),
    CONSTRAINT replies_comment_id_foreign FOREIGN KEY (comment_id)
        REFERENCES public.comments (id) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE CASCADE,
    CONSTRAINT replies_user_id_foreign FOREIGN KEY (user_id)
        REFERENCES public.users (id) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE CASCADE,
    CONSTRAINT replies_id_check CHECK (id > 0),
    CONSTRAINT replies_comment_id_check CHECK (comment_id > 0),
    CONSTRAINT replies_user_id_check CHECK (user_id > 0),
    CONSTRAINT replies_likes_check CHECK (likes >= 0),
    CONSTRAINT replies_dislikes_check CHECK (dislikes >= 0)
)

TABLESPACE pg_default;

ALTER TABLE IF EXISTS public.replies
    OWNER to postgres;

-- Table: public.saved_moodboards

-- DROP TABLE IF EXISTS public.saved_moodboards;

CREATE TABLE IF NOT EXISTS public.saved_moodboards
(
    id bigint NOT NULL GENERATED ALWAYS AS IDENTITY ( INCREMENT 1 START 1000 MINVALUE 1 MAXVALUE 9223372036854775807 CACHE 1 ),
    user_id bigint NOT NULL,
    mood_board_id bigint NOT NULL,
    created_at timestamp without time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT saved_moodboards_pkey PRIMARY KEY (id),
    CONSTRAINT saved_moodboards_ibfk_1 FOREIGN KEY (user_id)
        REFERENCES public.users (id) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE CASCADE,
    CONSTRAINT saved_moodboards_ibfk_2 FOREIGN KEY (mood_board_id)
        REFERENCES public.mood_boards (id) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE CASCADE,
    CONSTRAINT saved_moodboards_id_check CHECK (id > 0),
    CONSTRAINT saved_moodboards_user_id_check CHECK (user_id > 0),
    CONSTRAINT saved_moodboards_mood_board_id_check CHECK (mood_board_id > 0)
)

TABLESPACE pg_default;

ALTER TABLE IF EXISTS public.saved_moodboards
    OWNER to postgres;

-- Table: public.seen_content

-- DROP TABLE IF EXISTS public.seen_content;

CREATE TABLE IF NOT EXISTS public.seen_content
(
    id bigint NOT NULL DEFAULT nextval('seen_content_id_seq'::regclass),
    user_id bigint NOT NULL,
    content_type character varying(20) COLLATE pg_catalog."default" NOT NULL,
    content_id bigint NOT NULL,
    seen_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT seen_content_pkey PRIMARY KEY (id),
    CONSTRAINT seen_content_user_id_content_type_content_id_key UNIQUE (user_id, content_type, content_id),
    CONSTRAINT seen_content_user_id_fkey FOREIGN KEY (user_id)
        REFERENCES public.users (id) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE CASCADE,
    CONSTRAINT seen_content_content_type_check CHECK (content_type::text = ANY (ARRAY['board'::character varying, 'teaser'::character varying]::text[]))
)

TABLESPACE pg_default;

ALTER TABLE IF EXISTS public.seen_content
    OWNER to postgres;

-- Table: public.sessions

-- DROP TABLE IF EXISTS public.sessions;

CREATE TABLE IF NOT EXISTS public.sessions
(
    id character varying(255) COLLATE pg_catalog."default" NOT NULL,
    user_id bigint,
    ip_address character varying(45) COLLATE pg_catalog."default" DEFAULT NULL::character varying,
    user_agent text COLLATE pg_catalog."default",
    payload text COLLATE pg_catalog."default" NOT NULL,
    last_activity integer NOT NULL,
    CONSTRAINT sessions_pkey PRIMARY KEY (id),
    CONSTRAINT sessions_user_id_check CHECK (user_id > 0)
)

TABLESPACE pg_default;

ALTER TABLE IF EXISTS public.sessions
    OWNER to postgres;

-- Table: public.teaser_comment_reactions

-- DROP TABLE IF EXISTS public.teaser_comment_reactions;

CREATE TABLE IF NOT EXISTS public.teaser_comment_reactions
(
    id integer NOT NULL DEFAULT nextval('teaser_comment_reactions_id_seq'::regclass),
    comment_id integer NOT NULL,
    user_id integer NOT NULL,
    reaction_type character varying(10) COLLATE pg_catalog."default" NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT teaser_comment_reactions_pkey PRIMARY KEY (id),
    CONSTRAINT teaser_comment_reactions_comment_id_user_id_key UNIQUE (comment_id, user_id),
    CONSTRAINT teaser_comment_reactions_comment_id_fkey FOREIGN KEY (comment_id)
        REFERENCES public.teaser_comments (id) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE CASCADE,
    CONSTRAINT teaser_comment_reactions_user_id_fkey FOREIGN KEY (user_id)
        REFERENCES public.users (id) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE CASCADE,
    CONSTRAINT teaser_comment_reactions_reaction_type_check CHECK (reaction_type::text = ANY (ARRAY['like'::character varying, 'dislike'::character varying]::text[]))
)

TABLESPACE pg_default;

ALTER TABLE IF EXISTS public.teaser_comment_reactions
    OWNER to postgres;

-- Table: public.teaser_comment_replies

-- DROP TABLE IF EXISTS public.teaser_comment_replies;

CREATE TABLE IF NOT EXISTS public.teaser_comment_replies
(
    id integer NOT NULL DEFAULT nextval('teaser_comment_replies_id_seq'::regclass),
    comment_id integer NOT NULL,
    user_id integer NOT NULL,
    body text COLLATE pg_catalog."default" NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT teaser_comment_replies_pkey PRIMARY KEY (id),
    CONSTRAINT teaser_comment_replies_comment_id_fkey FOREIGN KEY (comment_id)
        REFERENCES public.teaser_comments (id) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE CASCADE,
    CONSTRAINT teaser_comment_replies_user_id_fkey FOREIGN KEY (user_id)
        REFERENCES public.users (id) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE CASCADE
)

TABLESPACE pg_default;

ALTER TABLE IF EXISTS public.teaser_comment_replies
    OWNER to postgres;

-- Table: public.teaser_comments

-- DROP TABLE IF EXISTS public.teaser_comments;

CREATE TABLE IF NOT EXISTS public.teaser_comments
(
    id bigint NOT NULL DEFAULT nextval('teaser_comments_id_seq'::regclass),
    teaser_id bigint NOT NULL,
    user_id bigint NOT NULL,
    body text COLLATE pg_catalog."default" NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT teaser_comments_pkey PRIMARY KEY (id),
    CONSTRAINT teaser_comments_teaser_id_fkey FOREIGN KEY (teaser_id)
        REFERENCES public.teasers (id) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE CASCADE,
    CONSTRAINT teaser_comments_user_id_fkey FOREIGN KEY (user_id)
        REFERENCES public.users (id) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE CASCADE
)

TABLESPACE pg_default;

ALTER TABLE IF EXISTS public.teaser_comments
    OWNER to postgres;

-- Table: public.teaser_reactions

-- DROP TABLE IF EXISTS public.teaser_reactions;

CREATE TABLE IF NOT EXISTS public.teaser_reactions
(
    id bigint NOT NULL DEFAULT nextval('teaser_reactions_id_seq'::regclass),
    teaser_id bigint NOT NULL,
    user_id bigint NOT NULL,
    reaction character varying(16) COLLATE pg_catalog."default" NOT NULL,
    CONSTRAINT teaser_reactions_pkey PRIMARY KEY (id),
    CONSTRAINT teaser_reactions_unique UNIQUE (teaser_id, user_id),
    CONSTRAINT reaction_check CHECK (reaction::text = ANY (ARRAY['fire'::character varying, 'love'::character varying, 'boring'::character varying]::text[]))
)

TABLESPACE pg_default;

ALTER TABLE IF EXISTS public.teaser_reactions
    OWNER to postgres;

-- Table: public.teaser_saves

-- DROP TABLE IF EXISTS public.teaser_saves;

CREATE TABLE IF NOT EXISTS public.teaser_saves
(
    id bigint NOT NULL DEFAULT nextval('teaser_saves_id_seq'::regclass),
    teaser_id bigint NOT NULL,
    user_id bigint NOT NULL,
    CONSTRAINT teaser_saves_pkey PRIMARY KEY (id),
    CONSTRAINT teaser_saves_teaser_id_user_id_key UNIQUE (teaser_id, user_id),
    CONSTRAINT teaser_saves_teaser_id_fkey FOREIGN KEY (teaser_id)
        REFERENCES public.teasers (id) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE CASCADE,
    CONSTRAINT teaser_saves_user_id_fkey FOREIGN KEY (user_id)
        REFERENCES public.users (id) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE CASCADE
)

TABLESPACE pg_default;

ALTER TABLE IF EXISTS public.teaser_saves
    OWNER to postgres;

-- Table: public.teasers

-- DROP TABLE IF EXISTS public.teasers;

CREATE TABLE IF NOT EXISTS public.teasers
(
    id bigint NOT NULL GENERATED ALWAYS AS IDENTITY ( INCREMENT 1 START 1000 MINVALUE 1 MAXVALUE 9223372036854775807 CACHE 1 ),
    user_id bigint NOT NULL,
    teaser_id character varying(255) COLLATE pg_catalog."default" NOT NULL,
    video character varying(1024) COLLATE pg_catalog."default",
    hashtags text COLLATE pg_catalog."default",
    created_at timestamp without time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_after integer,
    expires_on timestamp without time zone,
    description text COLLATE pg_catalog."default",
    teaser_mood character varying(16) COLLATE pg_catalog."default" NOT NULL,
    CONSTRAINT teasers_pkey PRIMARY KEY (id),
    CONSTRAINT teasers_ibfk_1 FOREIGN KEY (user_id)
        REFERENCES public.users (id) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE CASCADE,
    CONSTRAINT teasers_id_check CHECK (id > 0),
    CONSTRAINT teasers_user_id_check CHECK (user_id > 0),
    CONSTRAINT teasers_expires_after_check CHECK (expires_after > 0),
    CONSTRAINT teaser_mood_check CHECK (teaser_mood::text = ANY (ARRAY['hype'::character varying, 'funny'::character varying, 'shock'::character varying, 'love'::character varying]::text[]))
)

TABLESPACE pg_default;

ALTER TABLE IF EXISTS public.teasers
    OWNER to postgres;

-- Table: public.user_favorite_moodboards

-- DROP TABLE IF EXISTS public.user_favorite_moodboards;

CREATE TABLE IF NOT EXISTS public.user_favorite_moodboards
(
    id bigint NOT NULL GENERATED ALWAYS AS IDENTITY ( INCREMENT 1 START 1000 MINVALUE 1 MAXVALUE 9223372036854775807 CACHE 1 ),
    user_id bigint NOT NULL,
    moodboard_id bigint NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT user_favorite_moodboards_pkey PRIMARY KEY (id),
    CONSTRAINT fk_fav_moodboard FOREIGN KEY (moodboard_id)
        REFERENCES public.mood_boards (id) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE CASCADE,
    CONSTRAINT fk_fav_user FOREIGN KEY (user_id)
        REFERENCES public.users (id) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE CASCADE,
    CONSTRAINT user_favorite_moodboards_id_check CHECK (id > 0),
    CONSTRAINT user_favorite_moodboards_user_id_check CHECK (user_id > 0),
    CONSTRAINT user_favorite_moodboards_moodboard_id_check CHECK (moodboard_id > 0)
)

TABLESPACE pg_default;

ALTER TABLE IF EXISTS public.user_favorite_moodboards
    OWNER to postgres;

-- Table: public.user_files

-- DROP TABLE IF EXISTS public.user_files;

CREATE TABLE IF NOT EXISTS public.user_files
(
    id bigint NOT NULL GENERATED ALWAYS AS IDENTITY ( INCREMENT 1 START 1000 MINVALUE 1 MAXVALUE 9223372036854775807 CACHE 1 ),
    user_id bigint NOT NULL,
    filename character varying(255) COLLATE pg_catalog."default" NOT NULL,
    path character varying(255) COLLATE pg_catalog."default" NOT NULL,
    created_at timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
    updated_at timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
    content_type character varying(30) COLLATE pg_catalog."default" NOT NULL DEFAULT 'safe'::character varying,
    file_type character varying(30) COLLATE pg_catalog."default" NOT NULL DEFAULT 'image'::character varying,
    mime_type character varying(255) COLLATE pg_catalog."default" DEFAULT NULL::character varying,
    size bigint,
    CONSTRAINT user_files_pkey PRIMARY KEY (id),
    CONSTRAINT user_files_user_id_foreign FOREIGN KEY (user_id)
        REFERENCES public.users (id) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE CASCADE,
    CONSTRAINT user_files_id_check CHECK (id > 0),
    CONSTRAINT user_files_user_id_check CHECK (user_id > 0),
    CONSTRAINT user_files_content_type_check CHECK (content_type::text = ANY (ARRAY['safe'::character varying, 'adult'::character varying]::text[])),
    CONSTRAINT user_files_file_type_check CHECK (file_type::text = ANY (ARRAY['video'::character varying, 'image'::character varying]::text[])),
    CONSTRAINT user_files_size_check CHECK (size > 0)
)

TABLESPACE pg_default;

ALTER TABLE IF EXISTS public.user_files
    OWNER to postgres;

-- Table: public.users

-- DROP TABLE IF EXISTS public.users;

CREATE TABLE IF NOT EXISTS public.users
(
    id bigint NOT NULL GENERATED ALWAYS AS IDENTITY ( INCREMENT 1 START 1000 MINVALUE 1 MAXVALUE 9223372036854775807 CACHE 1 ),
    username character varying(255) COLLATE pg_catalog."default" NOT NULL,
    email character varying(255) COLLATE pg_catalog."default" NOT NULL,
    email_verified_at timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
    password character varying(255) COLLATE pg_catalog."default" NOT NULL,
    remember_token character varying(100) COLLATE pg_catalog."default" DEFAULT NULL::character varying,
    created_at timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
    updated_at timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
    CONSTRAINT users_pkey PRIMARY KEY (id),
    CONSTRAINT users_id_check CHECK (id > 0)
)

TABLESPACE pg_default;

ALTER TABLE IF EXISTS public.users
    OWNER to postgres;