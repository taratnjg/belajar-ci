<?= $this->extend('layout') ?>
<?= $this->section('content') ?>

<?php if (session()->getFlashData('success')): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <?= session()->getFlashData('success') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (session()->getFlashData('failed')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= session()->getFlashData('failed') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<a type="button" class="btn btn-success" href="<?= base_url() ?>transaksi/downloadTransaksi">Download Data</a>

<table class="table datatable">
    <thead>
        <tr>
            <th>Username</th>
            <th>Total Harga</th>
            <th>Alamat</th>
            <th>Ongkir</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($transactions as $transaction):?>
<tr>
    <td><?= $transaction['username']?></td>
    <td><?= 'IDR ' . number_format($transaction['total_harga'], 0, ',') ?></td>
    <td><?= $transaction['alamat']?></td>
    <td><?= 'IDR ' . number_format($transaction['ongkir'], 0, ',') ?></td>
    <td><?= $transaction['status']?></td>
    <td>
        <!-- Button trigger modal -->
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#updateStatusModal<?= $transaction['id']?>">
            Update Status
        </button>

        <!-- Modal -->
        <div class="modal fade" id="updateStatusModal<?= $transaction['id'] ?>" tabindex="-1" aria-labelledby="updateStatusModalLabel<?= $transaction['id'] ?>" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateStatusModalLabel<?= $transaction['id'] ?>">Edit Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="<?= base_url('transaksi/updateStatus') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $transaction['id'] ?>">
                    <div class="mb-3">
                        <label for="statusSelect<?= $transaction['id'] ?>" class="form-label">Status</label>
                        <select id="statusSelect<?= $transaction['id'] ?>" name="status" class="form-select">
                            <option value="0" <?= $transaction['status'] == '0' ? 'selected' : '' ?>>0</option>
                            <option value="1" <?= $transaction['status'] == '1' ? 'selected' : '' ?>>1</option>
                        </select>
                    </div>
                                                <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                            </div>

                </form>
            </div>
        </div>
    </div>
</div>

    </td>
</tr>
<?php endforeach;?>
    </tbody>
</table>

<?= $this->endSection() ?>
