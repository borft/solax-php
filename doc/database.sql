CREATE TABLE public.electricity (
    sample timestamp without time zone NOT NULL,
    kwh_in_1 numeric(9,3),
    kwh_in_2 numeric(9,3),
    kwh_out_1 numeric(9,3),
    kwh_out_2 numeric(9,3),
    power_in numeric(5,3),
    power_out numeric(5,3),
    current_l1 smallint,
    current_l2 smallint,
    current_l3 smallint,
    power_in_l1 numeric(5,3),
    power_in_l2 numeric(5,3),
    power_in_l3 numeric(5,3),
    power_out_l1 numeric(5,3),
    power_out_l2 numeric(5,3),
    power_out_l3 numeric(5,3)
);

CREATE TABLE public.solax (
    sample timestamp without time zone NOT NULL,
    current_dc_1 numeric(4,1) DEFAULT 0,
    current_dc_2 numeric(4,1) DEFAULT 0,
    voltage_dc_1 numeric(4,1) DEFAULT 0,
    voltage_dc_2 numeric(4,1) DEFAULT 0,
    power_dc_1 smallint DEFAULT 0,
    power_dc_2 smallint DEFAULT 0,
    current_ac numeric(4,1) DEFAULT 0,
    voltage_ac numeric(4,1) DEFAULT 0,
    power_ac smallint DEFAULT 0,
    yield_today numeric(4,1) DEFAULT 0,
    yield_total numeric(6,1) DEFAULT 0,
    net_frequency numeric(4,2) DEFAULT 0,
    temperature integer DEFAULT 0
);

ALTER TABLE ONLY public.electricity
    ADD CONSTRAINT electricity_pkey PRIMARY KEY (sample);


ALTER TABLE ONLY public.solax
    ADD CONSTRAINT solax_pkey PRIMARY KEY (sample);



