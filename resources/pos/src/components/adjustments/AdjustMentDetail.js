import React, { useEffect } from 'react';
import { connect } from 'react-redux';
import Modal from 'react-bootstrap/Modal';
import { fetchFrontSetting } from '../../store/action/frontSettingAction';
import { getAdjustmentDetails } from '../../store/action/adjustMentDetailsAction';
import Table from 'react-bootstrap/Table';
import moment from 'moment';
import { getFormattedMessage } from '../../shared/sharedMethod';

const AdjustMentDetail = ( props ) => {
    const { onDetails, lgShow, setLgShow, fetchFrontSetting, adjustmentsDetails, getAdjustmentDetails } = props;

    useEffect( () => {
        fetchFrontSetting()
    }, [] )

    useEffect( () => {
        if ( onDetails !== null ) {
            getAdjustmentDetails( onDetails )
        }
    }, [ onDetails ] )

    const onsetLgShow = () => {
        setLgShow( false )
    }

    const attributes = adjustmentsDetails && adjustmentsDetails.attributes;
    const items = attributes && attributes.adjustment_items ? attributes.adjustment_items : [];
    const movementSummary = items.length
        ? [...new Set(items.map((item) => item.tipo_movimiento || (item.method_type === 1 ? "entrada" : "salida")))]
        : [];
    const movementLabel = movementSummary.length === 1 ? movementSummary[0] : "mixto";
    const movementBadgeClass = movementLabel === "entrada"
        ? "bg-light-success"
        : movementLabel === "salida"
            ? "bg-light-danger"
            : "bg-light-primary";
    const emptyValue = "No registrado";

    return (
        <div>
            <Modal
                size="lg"
                centered
                aria-labelledby="example-custom-modal-styling-title"
                show={lgShow}
                onHide={() => onsetLgShow()}
            >
                <Modal.Header closeButton>
                    <Modal.Title id="example-modal-sizes-title-lg">
                        {getFormattedMessage( "adjustments.detail.title" )}
                    </Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <div className='mw-100 overflow-auto'>
                        <div className='mx-2 mb-4'>
                            <div className='row g-3'>
                                <div className='col-md-4'>
                                    <div className='border rounded-2 p-3 h-100'>
                                        <div className='text-gray-600 fs-small text-uppercase mb-1'>{getFormattedMessage( "react-data-table.date.column.label" )}</div>
                                        <div className='fw-bold text-gray-800'>{attributes ? moment( attributes.created_at || attributes.date ).format( 'YYYY-MM-DD LT' ) : emptyValue}</div>
                                    </div>
                                </div>
                                <div className='col-md-4'>
                                    <div className='border rounded-2 p-3 h-100'>
                                        <div className='text-gray-600 fs-small text-uppercase mb-1'>{getFormattedMessage( "globally.detail.reference" )}</div>
                                        <span className='badge bg-light-danger'>{attributes && attributes.reference_code ? attributes.reference_code : emptyValue}</span>
                                    </div>
                                </div>
                                <div className='col-md-4'>
                                    <div className='border rounded-2 p-3 h-100'>
                                        <div className='text-gray-600 fs-small text-uppercase mb-1'>{getFormattedMessage( "globally.type.label" )}</div>
                                        <span className={`badge ${movementBadgeClass}`}>{movementLabel.toUpperCase()}</span>
                                    </div>
                                </div>
                                <div className='col-md-6'>
                                    <div className='border rounded-2 p-3 h-100'>
                                        <div className='text-gray-600 fs-small text-uppercase mb-1'>{getFormattedMessage( "globally.detail.warehouse" )}</div>
                                        <div className='fw-bold text-gray-800'>{attributes && attributes.warehouse_name ? attributes.warehouse_name : emptyValue}</div>
                                    </div>
                                </div>
                                <div className='col-md-6'>
                                    <div className='border rounded-2 p-3 h-100'>
                                        <div className='text-gray-600 fs-small text-uppercase mb-1'>{getFormattedMessage("globally.user.label")}</div>
                                        <div className='fw-bold text-gray-800'>{attributes && attributes.user_name ? attributes.user_name : emptyValue}</div>
                                    </div>
                                </div>
                                <div className='col-12'>
                                    <div className='border rounded-2 p-3'>
                                        <div className='text-gray-600 fs-small text-uppercase mb-1'>Motivo del ajuste</div>
                                        <div className='text-gray-800' style={{ whiteSpace: "pre-wrap" }}>{attributes && attributes.descripcion ? attributes.descripcion : emptyValue}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div className='mx-2'>
                            <div className='fw-bold text-gray-800 mb-2'>{getFormattedMessage( "product.title" )}</div>
                            <Table bordered hover responsive>
                                <thead>
                                    <tr>
                                        <th>{getFormattedMessage( "globally.detail.product" )}</th>
                                        <th>{getFormattedMessage( "product.product-details.code-product.label" )}</th>
                                        <th>{getFormattedMessage( "dashboard.stockAlert.quantity.label" )}</th>
                                        <th>{getFormattedMessage( "globally.type.label" )}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {items.map( ( item, index ) => {
                                        const movement = item.tipo_movimiento || (item.method_type === 1 ? "entrada" : "salida");
                                        return (
                                            <tr key={index}>
                                                <td className="word-break-all">
                                                    {item.product && item.product.name ? item.product.name : emptyValue}
                                                </td>
                                                <td>{item.product && item.product.code ? item.product.code : emptyValue}</td>
                                                <td>{item.quantity || 0}</td>
                                                <td>
                                                    <span className={`badge ${movement === "entrada" ? "bg-light-success" : "bg-light-danger"}`}>
                                                        {movement.toUpperCase()}
                                                    </span>
                                                </td>
                                            </tr>
                                        )
                                    } )}
                                </tbody>
                            </Table>
                        </div>
                    </div>
                </Modal.Body>
            </Modal>
        </div>
    )
};


const mapStateToProps = ( state ) => {
    const { adjustments, adjustmentsDetails, isLoading, frontSetting } = state;
    return { adjustments, adjustmentsDetails, isLoading, frontSetting };
};

export default connect( mapStateToProps, { fetchFrontSetting, getAdjustmentDetails } )( AdjustMentDetail );
