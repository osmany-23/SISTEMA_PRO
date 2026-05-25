import React from "react";
import moment from "moment";
import { connect } from "react-redux";
import ReactDataTable from "../../../../shared/table/ReactDataTable";
import { getFormattedMessage } from "../../../../shared/sharedMethod";
import { stockDetailsAdjustmentAction } from "../../../../store/action/stockDetailsAdjustmentAction";

const AdjustmentTabs = (props) => {
    const { id, isLoading, totalRecord, stockDetailsAdjustment, stockDetailsAdjustmentAction } = props;

    const itemsValue =
        stockDetailsAdjustment &&
        stockDetailsAdjustment.map((item) => ({
            id: item.id,
            date: item.created_at ? moment(item.created_at).format("YYYY-MM-DD") : "",
            time: item.created_at ? moment(item.created_at).format("LT") : "",
            product_name: item.product_name,
            reference_code: item.reference_code,
            warehouse_name: item.warehouse_name,
            user_name: item.user_name || "N/A",
            tipo_movimiento: item.tipo_movimiento,
            quantity: item.quantity,
            descripcion: item.descripcion || "N/A",
        }));

    const columns = [
        {
            name: getFormattedMessage("globally.react-table.column.created-date.label"),
            cell: (row) => (
                <span className="badge bg-light-primary">
                    <div className="mb-1">{row.time}</div>
                    <div>{row.date}</div>
                </span>
            ),
        },
        {
            name: getFormattedMessage("dashboard.recentSales.reference.label"),
            selector: (row) => row.reference_code,
        },
        {
            name: getFormattedMessage("warehouse.title"),
            selector: (row) => row.warehouse_name,
        },
        {
            name: getFormattedMessage("globally.user.label"),
            selector: (row) => row.user_name,
        },
        {
            name: getFormattedMessage("globally.type.label"),
            cell: (row) => (
                <span className={`badge ${row.tipo_movimiento === "entrada" ? "bg-light-success" : "bg-light-danger"}`}>
                    {row.tipo_movimiento === "entrada" ? "AJUSTE ENTRADA" : "AJUSTE SALIDA"}
                </span>
            ),
        },
        {
            name: getFormattedMessage("globally.detail.quantity"),
            cell: (row) => <span className="badge bg-light-danger">{row.quantity}</span>,
        },
        {
            name: getFormattedMessage("adjustments.description.label"),
            selector: (row) => row.descripcion,
        },
    ];

    const onChange = (filter) => {
        stockDetailsAdjustmentAction(id, filter, true);
    };

    return (
        <ReactDataTable
            columns={columns}
            items={itemsValue}
            onChange={onChange}
            isLoading={isLoading}
            totalRows={totalRecord}
        />
    );
};

const mapStateToProps = (state) => {
    const { isLoading, totalRecord, stockDetailsAdjustment } = state;
    return { isLoading, totalRecord, stockDetailsAdjustment };
};

export default connect(mapStateToProps, { stockDetailsAdjustmentAction })(AdjustmentTabs);
