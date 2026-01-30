ALTER TABLE energy_offers
    MODIFY supply_type ENUM('luce','gas','luce_gas') NOT NULL;
