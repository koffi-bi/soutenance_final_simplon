<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPaymentFieldsToPayments extends Migration
{
    public function up()
    {
        // 1. Ajouter les colonnes nécessaires aux paiements
        $this->forge->addColumn('payments', [
            'payment_mode' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'after'      => 'transaction_reference',
            ],
            'provider' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
                'null'       => true,
                'after'      => 'payment_method',
            ],
            'provider_transaction_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
                'after'      => 'provider',
            ],
        ]);

        // 2. Remplacer l'index non unique par un index unique
        $this->db->query(
            'ALTER TABLE payments
             DROP INDEX transaction_reference,
             ADD UNIQUE KEY uq_payments_transaction_reference
             (transaction_reference)'
        );

        // 3. Empêcher la suppression automatique des paiements
        // lorsqu'une réservation est supprimée.
        $this->db->query(
            'ALTER TABLE payments
             DROP FOREIGN KEY payments_reservation_id_foreign'
        );

        $this->db->query(
            'ALTER TABLE payments
             ADD CONSTRAINT payments_reservation_id_foreign
             FOREIGN KEY (reservation_id)
             REFERENCES reservations(id)
             ON DELETE RESTRICT'
        );

        // 4. Index unique pour les transactions des fournisseurs.
        // MySQL autorise plusieurs valeurs NULL.
        $this->db->query(
            'ALTER TABLE payments
             ADD UNIQUE KEY uq_payments_provider_transaction
             (provider, provider_transaction_id)'
        );
    }

    public function down()
    {
        // Restaurer la clé étrangère initiale
        $this->db->query(
            'ALTER TABLE payments
             DROP FOREIGN KEY payments_reservation_id_foreign'
        );

        $this->db->query(
            'ALTER TABLE payments
             ADD CONSTRAINT payments_reservation_id_foreign
             FOREIGN KEY (reservation_id)
             REFERENCES reservations(id)
             ON DELETE CASCADE'
        );

        // Restaurer les index initiaux
        $this->db->query(
            'ALTER TABLE payments
             DROP INDEX uq_payments_provider_transaction,
             DROP INDEX uq_payments_transaction_reference,
             ADD KEY transaction_reference (transaction_reference)'
        );

        // Supprimer les nouvelles colonnes
        $this->forge->dropColumn('payments', [
            'provider_transaction_id',
            'provider',
            'payment_mode',
        ]);
    }
}
